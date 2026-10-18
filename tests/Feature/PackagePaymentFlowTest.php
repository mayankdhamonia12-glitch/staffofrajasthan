<?php

use App\Models\Package;
use App\Models\PackageEntitlement;
use App\Models\PackagePayment;
use App\Models\PackagePurchase;
use App\Models\PaymentWebhookEvent;
use App\Models\PlatformSetting;
use App\Models\User;
use App\PackagePaymentStatus;
use App\PackagePurchaseStatus;
use App\PackageType;
use App\PaymentWebhookStatus;
use App\Services\PlatformSettingsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->withoutVite();
    config([
        'services.razorpay.key_id' => 'rzp_test_example',
        'services.razorpay.key_secret' => 'test-key-secret',
        'services.razorpay.webhook_secret' => 'test-webhook-secret',
        'services.razorpay.mode' => 'test',
        'services.razorpay.base_url' => 'https://api.razorpay.com/v1',
    ]);
});

function paymentTestPackage(array $attributes = []): Package
{
    static $sequence = 0;
    $sequence++;

    return Package::query()->create(array_merge([
        'type' => PackageType::JobPosting,
        'name' => 'Posting package '.$sequence,
        'description' => 'Test package description',
        'price_paise' => 49900,
        'currency' => 'INR',
        'job_posting_credits' => 2,
        'listing_validity_days' => 30,
        'features' => ['Verified package'],
        'display_order' => 0,
        'is_active' => true,
    ], $attributes));
}

function paymentTestSettings(bool $purchases = true, bool $paidPosting = false): PlatformSetting
{
    $settings = PlatformSetting::query()->firstOrCreate(['id' => 1], [
        'site_name' => 'Staff of Rajasthan', 'timezone' => 'UTC', 'locale' => 'en',
    ]);
    $settings->update(['package_purchases_enabled' => $purchases, 'paid_job_posting_required' => $paidPosting]);
    app()->forgetInstance(PlatformSettingsService::class);

    return $settings;
}

function paymentTestWebhook(PackagePayment $payment, array $overrides = []): array
{
    $payload = [
        'event' => 'payment.captured',
        'payload' => ['payment' => ['entity' => array_merge([
            'id' => 'pay_test_captured',
            'order_id' => $payment->provider_order_id,
            'amount' => $payment->amount_paise,
            'currency' => $payment->currency,
            'status' => 'captured',
            'captured' => true,
        ], $overrides)]],
    ];
    $raw = json_encode($payload, JSON_THROW_ON_ERROR);

    return [$raw, hash_hmac('sha256', $raw, 'test-webhook-secret')];
}

test('employer checkout creates a server-priced order and preserves the purchase snapshot', function () {
    paymentTestSettings();
    $employer = User::factory()->employer()->create();
    $package = paymentTestPackage();
    Http::fake(['https://api.razorpay.com/v1/orders' => Http::response([
        'id' => 'order_test_123', 'amount' => 49900, 'currency' => 'INR', 'status' => 'created',
    ], 200)]);

    $this->actingAs($employer)->get(route('employer.packages.index'))->assertOk()->assertSee($package->name);
    $this->get(route('employer.packages.review', $package))->assertOk()->assertSee('Continue to secure checkout');
    $this->post(route('employer.packages.checkout', $package), ['idempotency_key' => (string) Str::uuid()])
        ->assertRedirect();

    $purchase = PackagePurchase::query()->firstOrFail();
    $payment = $purchase->payments()->firstOrFail();
    expect($purchase->status)->toBe(PackagePurchaseStatus::Pending)
        ->and($purchase->amount_paise)->toBe(49900)
        ->and($purchase->package_snapshot['name'])->toBe($package->name)
        ->and($payment->provider_order_id)->toBe('order_test_123')
        ->and($payment->amount_paise)->toBe(49900)
        ->and(PackageEntitlement::query()->count())->toBe(0);
    $this->get(route('employer.purchases.show', $purchase))->assertOk()->assertSee('Pay');
    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.razorpay.com/v1/orders'
        && $request['amount'] === 49900 && $request['currency'] === 'INR');

    $snapshotName = $package->name;
    $package->update(['name' => 'Renamed package', 'price_paise' => 90000]);
    expect($purchase->fresh()->package_snapshot['name'])->toBe($snapshotName)
        ->and($purchase->fresh()->amount_paise)->toBe(49900);
});

test('fresh migrations include billing tables and platform package controls default to disabled', function () {
    expect(Schema::hasTable('packages'))
        ->toBeTrue()
        ->and(Schema::hasTable('package_payments'))->toBeTrue()
        ->and(Schema::hasTable('package_entitlements'))->toBeTrue()
        ->and(Schema::hasTable('payment_webhook_events'))->toBeTrue();

    $settings = app(PlatformSettingsService::class)->current();
    expect($settings->package_purchases_enabled)->toBeFalse()->and($settings->paid_job_posting_required)->toBeFalse();
});

test('only the owner can enable payment and paid job posting controls', function () {
    $owner = User::factory()->owner()->create();
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get(route('admin.settings.edit'))->assertForbidden();
    $this->actingAs($owner)->get(route('admin.settings.edit'))->assertOk()->assertSee('Enable package purchases');
    $this->patch(route('admin.settings.update'), [
        'site_name' => 'Staff of Rajasthan', 'contact_email' => null, 'contact_phone' => null,
        'timezone' => 'UTC', 'locale' => 'en', 'package_purchases_enabled' => 1, 'paid_job_posting_required' => 1,
    ])->assertRedirect();
    expect(PlatformSetting::query()->firstOrFail()->package_purchases_enabled)->toBeTrue()
        ->and(PlatformSetting::query()->firstOrFail()->paid_job_posting_required)->toBeTrue();
});

test('checkout is disabled unless the owner enables it and all mode-matched secrets are set', function () {
    paymentTestSettings(false);
    $employer = User::factory()->employer()->create();
    $package = paymentTestPackage();

    $this->actingAs($employer)->get(route('employer.packages.index'))->assertOk()->assertSee('currently unavailable');
    $this->post(route('employer.packages.checkout', $package), ['idempotency_key' => (string) Str::uuid()])
        ->assertSessionHasErrors('checkout');
    expect(PackagePurchase::query()->count())->toBe(0);

    paymentTestSettings();
    config(['services.razorpay.key_id' => 'rzp_live_wrong_mode']);
    $this->post(route('employer.packages.checkout', $package), ['idempotency_key' => (string) Str::uuid()])
        ->assertSessionHasErrors('checkout');
    expect(PackagePurchase::query()->count())->toBe(0);
});

test('duplicate checkout submissions reuse the same order and failed order creation can retry the same purchase', function () {
    paymentTestSettings();
    $employer = User::factory()->employer()->create();
    $package = paymentTestPackage();
    Http::fake([
        'https://api.razorpay.com/v1/orders' => Http::sequence()
            ->push(['error' => ['code' => 'BAD_REQUEST_ERROR']], 400)
            ->push(['id' => 'order_retry123', 'amount' => 49900, 'currency' => 'INR'], 200),
    ]);
    $idempotencyKey = (string) Str::uuid();

    $this->actingAs($employer)->post(route('employer.packages.checkout', $package), ['idempotency_key' => $idempotencyKey])->assertRedirect();
    $purchase = PackagePurchase::query()->firstOrFail();
    expect($purchase->payments()->count())->toBe(1)
        ->and($purchase->payments()->firstOrFail()->status)->toBe(PackagePaymentStatus::Failed);

    $this->post(route('employer.purchases.retry', $purchase))->assertRedirect(route('employer.purchases.show', $purchase));
    expect(PackagePurchase::query()->count())->toBe(1)
        ->and($purchase->fresh()->status)->toBe(PackagePurchaseStatus::Pending)
        ->and($purchase->payments()->count())->toBe(2)
        ->and($purchase->payments()->latest('id')->firstOrFail()->provider_order_id)->toBe('order_retry123');
});

test('duplicate checkout submissions with the same idempotency key reuse one successful provider order', function () {
    paymentTestSettings();
    $employer = User::factory()->employer()->create();
    $package = paymentTestPackage();
    Http::fake(['https://api.razorpay.com/v1/orders' => Http::response([
        'id' => 'order_idempotent123', 'amount' => 49900, 'currency' => 'INR',
    ], 200)]);
    $key = (string) Str::uuid();
    $payload = ['idempotency_key' => $key];

    $this->actingAs($employer)->post(route('employer.packages.checkout', $package), $payload)->assertRedirect();
    $this->post(route('employer.packages.checkout', $package), $payload)->assertRedirect();
    expect(PackagePurchase::query()->count())->toBe(1)
        ->and(PackagePayment::query()->count())->toBe(1)
        ->and(PackagePayment::query()->firstOrFail()->provider_order_id)->toBe('order_idempotent123');
    Http::assertSentCount(1);
});

test('package administrators can edit and deactivate a catalog package without deleting it', function () {
    $admin = User::factory()->admin()->create();
    $package = paymentTestPackage();
    $this->actingAs($admin)->patch(route('admin.packages.update', $package), [
        'type' => 'job_posting', 'name' => 'Updated posting package', 'description' => 'Updated details.',
        'price' => '799.99', 'job_posting_credits' => 5, 'listing_validity_days' => 60,
        'features' => ['Longer listing'], 'display_order' => 2, 'is_active' => 1,
    ])->assertRedirect(route('admin.packages.index'));
    $this->patch(route('admin.packages.active', $package), ['is_active' => 0])->assertRedirect();

    expect($package->fresh()->name)->toBe('Updated posting package')
        ->and($package->fresh()->price_paise)->toBe(79999)
        ->and($package->fresh()->is_active)->toBeFalse();
});

test('checkout callback requires valid signature and provider-confirmed captured amount before granting one entitlement', function () {
    paymentTestSettings();
    $employer = User::factory()->employer()->create();
    $package = paymentTestPackage();
    Http::fake([
        'https://api.razorpay.com/v1/orders' => Http::response(['id' => 'order_test456', 'amount' => 49900, 'currency' => 'INR'], 200),
        'https://api.razorpay.com/v1/payments/pay_test456' => Http::response([
            'id' => 'pay_test456', 'order_id' => 'order_test456', 'amount' => 49900,
            'currency' => 'INR', 'status' => 'captured', 'captured' => true,
        ], 200),
    ]);
    $this->actingAs($employer)->post(route('employer.packages.checkout', $package), ['idempotency_key' => (string) Str::uuid()])->assertRedirect();
    $purchase = PackagePurchase::query()->firstOrFail();
    $payment = $purchase->payments()->firstOrFail();

    $this->post(route('employer.purchases.verify', $purchase), [
        'razorpay_order_id' => 'order_test456', 'razorpay_payment_id' => 'pay_test456', 'razorpay_signature' => str_repeat('a', 64),
    ])->assertSessionHasErrors('payment');
    expect(PackageEntitlement::query()->count())->toBe(0);

    $signature = hash_hmac('sha256', 'order_test456|pay_test456', 'test-key-secret');
    $this->post(route('employer.purchases.verify', $purchase), [
        'razorpay_order_id' => 'order_test456', 'razorpay_payment_id' => 'pay_test456', 'razorpay_signature' => $signature,
    ])->assertRedirect(route('employer.purchases.show', $purchase));
    expect($purchase->fresh()->status)->toBe(PackagePurchaseStatus::Paid)
        ->and($payment->fresh()->status)->toBe(PackagePaymentStatus::Captured)
        ->and(PackageEntitlement::query()->count())->toBe(1)
        ->and(PackageEntitlement::query()->firstOrFail()->credits_remaining)->toBe(2);
});

test('provider mismatch and another employers purchase reference never grant or reveal an entitlement', function () {
    paymentTestSettings();
    $employer = User::factory()->employer()->create();
    $otherEmployer = User::factory()->employer()->create();
    $package = paymentTestPackage();
    Http::fake([
        'https://api.razorpay.com/v1/orders' => Http::response(['id' => 'order_test789', 'amount' => 49900, 'currency' => 'INR'], 200),
        'https://api.razorpay.com/v1/payments/pay_test789' => Http::response([
            'id' => 'pay_test789', 'order_id' => 'order_test789', 'amount' => 100, 'currency' => 'INR', 'status' => 'captured', 'captured' => true,
        ], 200),
    ]);
    $this->actingAs($employer)->post(route('employer.packages.checkout', $package), ['idempotency_key' => (string) Str::uuid()])->assertRedirect();
    $purchase = PackagePurchase::query()->firstOrFail();
    $this->actingAs($otherEmployer)->get(route('employer.purchases.show', $purchase))->assertNotFound();
    $signature = hash_hmac('sha256', 'order_test789|pay_test789', 'test-key-secret');
    $this->actingAs($employer)->post(route('employer.purchases.verify', $purchase), [
        'razorpay_order_id' => 'order_test789', 'razorpay_payment_id' => 'pay_test789', 'razorpay_signature' => $signature,
    ])->assertRedirect();
    expect($purchase->fresh()->status)->toBe(PackagePurchaseStatus::Pending)
        ->and(PackageEntitlement::query()->count())->toBe(0);
});

test('webhook validates exact raw body signature and captures idempotently by provider event id', function () {
    paymentTestSettings();
    $employer = User::factory()->employer()->create();
    $package = paymentTestPackage();
    Http::fake(['https://api.razorpay.com/v1/orders' => Http::response(['id' => 'order_test_hook', 'amount' => 49900, 'currency' => 'INR'], 200)]);
    $this->actingAs($employer)->post(route('employer.packages.checkout', $package), ['idempotency_key' => (string) Str::uuid()])->assertRedirect();
    $purchase = PackagePurchase::query()->firstOrFail();
    $payment = $purchase->payments()->firstOrFail();
    [$raw, $signature] = paymentTestWebhook($payment);

    $this->call('POST', '/webhooks/razorpay', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_RAZORPAY_SIGNATURE' => str_repeat('0', 64), 'HTTP_X_RAZORPAY_EVENT_ID' => 'evt_bad'], $raw)->assertUnauthorized();
    expect(PackageEntitlement::query()->count())->toBe(0);

    $call = fn () => $this->call('POST', '/webhooks/razorpay', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_RAZORPAY_SIGNATURE' => $signature, 'HTTP_X_RAZORPAY_EVENT_ID' => 'evt_capture_1'], $raw);
    $call()->assertOk();
    $call()->assertOk();
    expect($purchase->fresh()->status)->toBe(PackagePurchaseStatus::Paid)
        ->and($payment->fresh()->status)->toBe(PackagePaymentStatus::Captured)
        ->and(PackageEntitlement::query()->count())->toBe(1)
        ->and(PaymentWebhookEvent::query()->where('event_id', 'evt_capture_1')->firstOrFail()->status)->toBe(PaymentWebhookStatus::Processed);
});

test('failed webhook grants no credits and a later verified capture can settle the pending purchase', function () {
    paymentTestSettings();
    $employer = User::factory()->employer()->create();
    $package = paymentTestPackage();
    Http::fake(['https://api.razorpay.com/v1/orders' => Http::response([
        'id' => 'order_latecapture123', 'amount' => 49900, 'currency' => 'INR',
    ], 200)]);
    $this->actingAs($employer)->post(route('employer.packages.checkout', $package), ['idempotency_key' => Str::uuid()->toString()])->assertRedirect();
    $purchase = PackagePurchase::query()->firstOrFail();
    $payment = $purchase->payments()->firstOrFail();

    $failedPayload = ['event' => 'payment.failed', 'payload' => ['payment' => ['entity' => [
        'id' => 'pay_latecapture123', 'order_id' => $payment->provider_order_id, 'amount' => 49900,
        'currency' => 'INR', 'status' => 'failed', 'captured' => false,
    ]]]];
    $failedRaw = json_encode($failedPayload, JSON_THROW_ON_ERROR);
    $failedSignature = hash_hmac('sha256', $failedRaw, 'test-webhook-secret');
    $this->call('POST', '/webhooks/razorpay', [], [], [], [
        'CONTENT_TYPE' => 'application/json', 'HTTP_X_RAZORPAY_SIGNATURE' => $failedSignature, 'HTTP_X_RAZORPAY_EVENT_ID' => 'evt_failed_1',
    ], $failedRaw)->assertOk();
    expect($purchase->fresh()->status)->toBe(PackagePurchaseStatus::Failed)
        ->and(PackageEntitlement::query()->count())->toBe(0);

    [$capturedRaw, $capturedSignature] = paymentTestWebhook($payment, ['id' => 'pay_latecapture123']);
    $this->call('POST', '/webhooks/razorpay', [], [], [], [
        'CONTENT_TYPE' => 'application/json', 'HTTP_X_RAZORPAY_SIGNATURE' => $capturedSignature, 'HTTP_X_RAZORPAY_EVENT_ID' => 'evt_late_capture_1',
    ], $capturedRaw)->assertOk();
    expect($purchase->fresh()->status)->toBe(PackagePurchaseStatus::Paid)
        ->and($payment->fresh()->status)->toBe(PackagePaymentStatus::Captured)
        ->and(PackageEntitlement::query()->count())->toBe(1);
});

test('admins can manage catalog records and inspect purchases but cannot manually settle a payment', function () {
    $admin = User::factory()->admin()->create();
    $candidate = User::factory()->candidate()->create();
    $package = paymentTestPackage();

    $this->actingAs($candidate)->get(route('admin.packages.index'))->assertForbidden();
    $this->actingAs($admin)->get(route('admin.packages.index'))->assertOk()->assertSee($package->name);
    $this->actingAs($admin)->get(route('admin.package-purchases.index'))->assertOk()->assertSee('cannot mark purchases as paid');
});

test('admin package creation validates package type and stores an exact integer INR amount', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->post(route('admin.packages.store'), [
        'type' => 'candidate_access', 'name' => 'Candidate contacts', 'description' => 'Private opt-in contact access.',
        'price' => '125.50', 'candidate_access_credits' => 10, 'entitlement_validity_days' => 30,
        'features' => ['Verified contact details', ''], 'display_order' => 1, 'is_active' => 1,
    ])->assertRedirect(route('admin.packages.index'));

    $package = Package::query()->firstOrFail();
    expect($package->price_paise)->toBe(12550)
        ->and($package->currency)->toBe('INR')
        ->and($package->type)->toBe(PackageType::CandidateAccess)
        ->and($package->features)->toBe(['Verified contact details']);
});
