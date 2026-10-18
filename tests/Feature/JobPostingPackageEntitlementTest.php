<?php

use App\Models\Company;
use App\Models\Industry;
use App\Models\Job;
use App\Models\Location;
use App\Models\Package;
use App\Models\PackageEntitlement;
use App\Models\PackagePayment;
use App\Models\PackagePurchase;
use App\Models\PlatformSetting;
use App\Models\User;
use App\PackagePaymentStatus;
use App\PackagePurchaseStatus;
use App\PackageType;
use App\Services\PackageSettlementService;
use App\Services\PlatformSettingsService;
use Illuminate\Support\Str;

beforeEach(fn () => $this->withoutVite());

function postingTestWorld(bool $requirePaid): array
{
    static $sequence = 0;
    $sequence++;
    $employer = User::factory()->employer()->create();
    $industry = Industry::query()->create(['name' => 'Posting Industry '.$sequence, 'slug' => 'posting-industry-'.$sequence, 'is_active' => true]);
    $location = Location::query()->create(['name' => 'Jaipur '.$sequence, 'state' => 'Rajasthan', 'slug' => 'posting-location-'.$sequence, 'is_active' => true]);
    $company = $employer->companies()->create([
        'industry_id' => $industry->getKey(), 'location_id' => $location->getKey(), 'name' => 'Posting Company '.$sequence,
        'slug' => 'posting-company-'.$sequence, 'description' => 'Company accepting applications.', 'is_visible' => true,
    ]);
    $settings = PlatformSetting::query()->firstOrCreate(['id' => 1], ['site_name' => 'Staff of Rajasthan', 'timezone' => 'UTC', 'locale' => 'en']);
    $settings->update(['package_purchases_enabled' => false, 'paid_job_posting_required' => $requirePaid]);
    app()->forgetInstance(PlatformSettingsService::class);

    return [$employer, $company, $industry, $location];
}

function grantPostingCredits(User $employer, int $credits = 1, int $validity = 30): PackageEntitlement
{
    static $sequence = 0;
    $sequence++;
    $package = Package::query()->create([
        'type' => PackageType::JobPosting, 'name' => 'Posting credits '.$sequence, 'price_paise' => 10000,
        'currency' => 'INR', 'job_posting_credits' => $credits, 'listing_validity_days' => $validity,
        'features' => [], 'is_active' => true, 'display_order' => 0,
    ]);
    $purchase = PackagePurchase::query()->create([
        'purchase_reference' => (string) Str::uuid(), 'idempotency_key' => (string) Str::uuid(),
        'buyer_user_id' => $employer->getKey(), 'package_id' => $package->getKey(),
        'package_snapshot' => ['type' => 'job_posting', 'name' => $package->name, 'job_posting_credits' => $credits, 'listing_validity_days' => $validity],
        'amount_paise' => 10000, 'currency' => 'INR', 'status' => PackagePurchaseStatus::Pending,
    ]);
    $payment = PackagePayment::query()->create([
        'package_purchase_id' => $purchase->getKey(), 'provider' => 'razorpay', 'provider_order_id' => 'order_seed_'.$sequence,
        'amount_paise' => 10000, 'currency' => 'INR', 'status' => PackagePaymentStatus::Created,
    ]);

    return app(PackageSettlementService::class)->settle($payment, 'pay_seed_'.$sequence);
}

function postingPayload(Company $company, Location $location, string $title = 'Paid listing'): array
{
    return [
        'company_id' => $company->getKey(), 'title' => $title, 'description' => 'A real role for a growing team.',
        'location_id' => $location->getKey(), 'employment_type' => 'full_time', 'intent' => 'publish',
    ];
}

test('paid posting consumes one settled credit only on first publication and reopens without another charge', function () {
    [$employer, $company, , $location] = postingTestWorld(true);
    $entitlement = grantPostingCredits($employer, 2, 21);

    $this->actingAs($employer)->post(route('employer.jobs.store'), postingPayload($company, $location))->assertRedirect();
    $job = Job::query()->where('title', 'Paid listing')->firstOrFail();
    $expiresAt = $job->package_expires_at;
    expect($entitlement->fresh()->credits_remaining)->toBe(1)
        ->and($job->packagePostingUse()->exists())->toBeTrue()
        ->and($expiresAt)->not->toBeNull();

    $this->patch(route('employer.jobs.lifecycle', $job), ['action' => 'close'])->assertRedirect();
    $this->patch(route('employer.jobs.lifecycle', $job), ['action' => 'reopen'])->assertRedirect();
    expect($entitlement->fresh()->credits_remaining)->toBe(1)
        ->and($job->fresh()->package_expires_at->equalTo($expiresAt))->toBeTrue()
        ->and($job->packagePostingUse()->count())->toBe(1);
});

test('paid posting requirement blocks publication without an entitlement credit', function () {
    [$employer, $company, , $location] = postingTestWorld(true);
    $this->actingAs($employer)->from(route('employer.jobs.create'))->post(route('employer.jobs.store'), postingPayload($company, $location, 'Blocked listing'))
        ->assertSessionHasErrors('intent');
    expect(Job::query()->where('title', 'Blocked listing')->exists())->toBeFalse();
});

test('free posting is available while owner-controlled paid enforcement is disabled', function () {
    [$freeEmployer, $freeCompany, , $freeLocation] = postingTestWorld(false);
    $freePostResponse = $this->actingAs($freeEmployer)->post(route('employer.jobs.store'), postingPayload($freeCompany, $freeLocation, 'Free listing'));
    $freePostResponse->assertRedirect()->assertSessionHasNoErrors();
    expect(Job::query()->where('title', 'Free listing')->firstOrFail()->packagePostingUse()->exists())->toBeFalse();
});

test('existing live listings are not retroactively charged when the owner enables paid posting', function () {
    [$employer, $company, $industry, $location] = postingTestWorld(false);
    $job = $company->jobs()->create([
        'industry_id' => $industry->getKey(), 'location_id' => $location->getKey(), 'title' => 'Existing free listing',
        'slug' => 'existing-free-listing-'.$company->getKey(), 'description' => 'A previously published job.',
        'employment_type' => 'full_time', 'status' => 'published', 'published_at' => now()->subDay(), 'is_filled' => false,
    ]);
    $settings = PlatformSetting::query()->firstOrFail();
    $settings->update(['paid_job_posting_required' => true]);
    app()->forgetInstance(PlatformSettingsService::class);

    $this->actingAs($employer)->patch(route('employer.jobs.update', $job), postingPayload($company, $location, 'Existing free listing'))
        ->assertRedirect();
    expect($job->fresh()->status)->toBe('published')
        ->and($job->packagePostingUse()->exists())->toBeFalse()
        ->and(PackageEntitlement::query()->count())->toBe(0);
});

test('expired paid listing is hidden from public jobs and cannot be reopened', function () {
    [$employer, $company, , $location] = postingTestWorld(true);
    $entitlement = grantPostingCredits($employer, 1, 1);
    $entitlement->update(['credits_remaining' => 0]);
    $job = $company->jobs()->create([
        'title' => 'Expired listing', 'slug' => 'expired-listing-'.$company->getKey(), 'description' => 'Expired role.',
        'location_id' => $location->getKey(), 'employment_type' => 'full_time', 'status' => 'closed', 'published_at' => now()->subDays(10),
        'is_filled' => false, 'package_expires_at' => now()->subDay(),
    ]);
    $job->packagePostingUse()->create(['package_entitlement_id' => $entitlement->getKey(), 'employer_user_id' => $employer->getKey(), 'consumed_at' => now()->subDays(2)]);

    $this->get(route('jobs.show', $job))->assertNotFound();
    $this->actingAs($employer)->patch(route('employer.jobs.lifecycle', $job), ['action' => 'reopen'])->assertSessionHasErrors('intent');
    expect($job->fresh()->status)->toBe('closed')->and($entitlement->fresh()->credits_remaining)->toBe(0);
});
