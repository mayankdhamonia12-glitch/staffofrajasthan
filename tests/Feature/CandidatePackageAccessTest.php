<?php

use App\Models\Package;
use App\Models\PackageEntitlement;
use App\Models\PackagePayment;
use App\Models\PackagePurchase;
use App\Models\User;
use App\PackagePaymentStatus;
use App\PackagePurchaseStatus;
use App\PackageType;
use App\Services\PackageSettlementService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->withoutVite();
    Storage::fake('local');
});

function contactTestCandidate(array $attributes = []): array
{
    static $sequence = 0;
    $sequence++;
    $candidate = User::factory()->candidate()->create(['email' => 'candidate'.$sequence.'@example.test']);
    $profile = $candidate->candidateProfile()->create(array_merge([
        'slug' => 'contact-candidate-'.$sequence, 'headline' => 'Senior product designer', 'phone' => '9876543210',
        'city' => 'Jaipur', 'state' => 'Rajasthan', 'is_public' => true, 'share_contact_details' => true,
        'share_resume_with_employers' => true, 'resume_path' => 'candidate-private/resumes/'.$sequence.'/cv.pdf',
        'resume_title' => 'Product design CV', 'resume_original_name' => 'cv.pdf',
    ], $attributes));
    Storage::disk('local')->put($profile->resume_path, 'private resume bytes');

    return [$candidate, $profile];
}

function contactTestEmployer(bool $approved = true): User
{
    static $sequence = 0;
    $sequence++;
    $employer = User::factory()->employer()->create();
    $employer->companies()->create([
        'name' => 'Contact Company '.$sequence, 'slug' => 'contact-company-'.$sequence,
        'description' => 'Company recruitment profile.', 'is_visible' => $approved,
    ]);

    return $employer;
}

function grantCandidateAccess(User $employer, int $credits = 1, int $validityDays = 30): PackageEntitlement
{
    static $sequence = 0;
    $sequence++;
    $package = Package::query()->create([
        'type' => PackageType::CandidateAccess, 'name' => 'Candidate contacts '.$sequence, 'price_paise' => 50000,
        'currency' => 'INR', 'candidate_access_credits' => $credits, 'entitlement_validity_days' => $validityDays,
        'features' => [], 'is_active' => true, 'display_order' => 0,
    ]);
    $purchase = PackagePurchase::query()->create([
        'purchase_reference' => (string) Str::uuid(), 'idempotency_key' => (string) Str::uuid(),
        'buyer_user_id' => $employer->getKey(), 'package_id' => $package->getKey(),
        'package_snapshot' => ['type' => 'candidate_access', 'name' => $package->name, 'candidate_access_credits' => $credits, 'entitlement_validity_days' => $validityDays],
        'amount_paise' => 50000, 'currency' => 'INR', 'status' => PackagePurchaseStatus::Pending,
    ]);
    $payment = PackagePayment::query()->create([
        'package_purchase_id' => $purchase->getKey(), 'provider' => 'razorpay', 'provider_order_id' => 'order_candidate_'.$sequence,
        'amount_paise' => 50000, 'currency' => 'INR', 'status' => PackagePaymentStatus::Created,
    ]);

    return app(PackageSettlementService::class)->settle($payment, 'pay_candidate_'.$sequence);
}

test('employer candidate access is private, opt-in gated, and consumes the credit once', function () {
    [$candidate, $profile] = contactTestCandidate();
    $employer = contactTestEmployer();
    $otherEmployer = contactTestEmployer();
    $entitlement = grantCandidateAccess($employer, 1);

    $this->actingAs($employer)->post(route('employer.candidate-access.unlock', $profile))->assertRedirect(route('employer.candidate-access.show', $profile));
    $this->post(route('employer.candidate-access.unlock', $profile))->assertRedirect(route('employer.candidate-access.show', $profile));
    $contactResponse = $this->get(route('employer.candidate-access.show', $profile))->assertOk();
    $contactResponse->assertHeaderContains('Cache-Control', 'no-store')->assertHeaderContains('Cache-Control', 'private')
        ->assertSee($candidate->email)->assertSee('9876543210');
    expect($entitlement->fresh()->credits_remaining)->toBe(0)
        ->and($profile->accesses()->count())->toBe(1);

    $this->get(route('employer.candidate-access.resume', $profile))->assertOk();
    Storage::disk('local')->assertExists($profile->resume_path);
    $this->actingAs($otherEmployer)->get(route('employer.candidate-access.show', $profile))->assertForbidden();
    $this->actingAs($otherEmployer)->get(route('employer.candidate-access.resume', $profile))->assertForbidden();
    $this->get(route('candidates.show', $profile->slug))->assertOk()->assertDontSee($candidate->email)->assertDontSee('9876543210')->assertDontSee('cv.pdf');
});

test('candidate remains in control of separate contact and resume sharing preferences', function () {
    [$candidate, $profile] = contactTestCandidate(['share_contact_details' => true, 'share_resume_with_employers' => false]);

    $this->actingAs($candidate)->patch(route('candidate.settings.update'), [
        'name' => $candidate->name, 'is_public' => 1, 'show_expected_salary' => 0,
        'share_contact_details' => 0, 'share_resume_with_employers' => 1,
    ])->assertRedirect();
    expect($profile->fresh()->share_contact_details)->toBeFalse()->and($profile->fresh()->share_resume_with_employers)->toBeTrue();

    $this->patch(route('candidate.settings.update'), ['name' => $candidate->name, 'is_public' => 1, 'show_expected_salary' => 0])->assertRedirect();
    expect($profile->fresh()->share_resume_with_employers)->toBeTrue();
});

test('unapproved employers, candidates, private profiles, and exhausted entitlements cannot unlock contact data', function () {
    [$candidate, $profile] = contactTestCandidate();
    $unapproved = contactTestEmployer(false);
    grantCandidateAccess($unapproved, 2);
    $this->actingAs($unapproved)->post(route('employer.candidate-access.unlock', $profile))->assertForbidden();
    $this->actingAs($candidate)->post(route('employer.candidate-access.unlock', $profile))->assertForbidden();

    $profile->update(['is_public' => false]);
    $approved = contactTestEmployer();
    grantCandidateAccess($approved, 1);
    $this->actingAs($approved)->post(route('employer.candidate-access.unlock', $profile))->assertNotFound();
    $profile->update(['is_public' => true]);
    $this->actingAs($approved)->post(route('employer.candidate-access.unlock', $profile))->assertRedirect(route('employer.candidate-access.show', $profile));
    $this->actingAs($approved)->get(route('employer.candidate-access.show', $profile))->assertOk();
    [, $secondProfile] = contactTestCandidate();
    $this->actingAs($approved)->post(route('employer.candidate-access.unlock', $secondProfile))->assertRedirect(route('employer.packages.index'));
    $this->get(route('employer.candidate-access.show', $secondProfile))->assertForbidden();
});

test('contact and resume fields opted out by a candidate stay unavailable even after unlock', function () {
    [$candidate, $profile] = contactTestCandidate(['share_contact_details' => false, 'share_resume_with_employers' => false]);
    $employer = contactTestEmployer();
    grantCandidateAccess($employer, 1);

    $this->actingAs($employer)->post(route('employer.candidate-access.unlock', $profile))->assertNotFound();
    $profile->update(['share_contact_details' => true]);
    $this->post(route('employer.candidate-access.unlock', $profile))->assertRedirect();
    $this->get(route('employer.candidate-access.show', $profile))->assertOk()->assertSee($candidate->email)->assertSee('9876543210');
    $this->get(route('employer.candidate-access.resume', $profile))->assertNotFound();
});

test('expired candidate access no longer reveals contact data or permits resume downloads', function () {
    [, $profile] = contactTestCandidate();
    $employer = contactTestEmployer();
    $entitlement = grantCandidateAccess($employer, 2, 30);

    $this->actingAs($employer)->post(route('employer.candidate-access.unlock', $profile))->assertRedirect();
    $access = $profile->accesses()->firstOrFail();
    $access->update(['valid_until' => now()->subSecond()]);
    $entitlement->update(['valid_until' => now()->subSecond()]);

    $this->get(route('employer.candidate-access.show', $profile))->assertForbidden();
    $this->get(route('employer.candidate-access.resume', $profile))->assertForbidden();
    $this->post(route('employer.candidate-access.unlock', $profile))->assertRedirect(route('employer.packages.index'));
});
