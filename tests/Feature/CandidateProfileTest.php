<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

test('a candidate can create and update only their own profile', function () {
    $candidate = User::factory()->candidate()->create();

    $this->actingAs($candidate)->patch(route('candidate.profile.update'), [
        'headline' => 'Laravel Developer',
        'location' => 'Jaipur, Rajasthan',
        'about' => 'Building thoughtful web applications.',
    ])->assertRedirect();

    $this->assertDatabaseHas('candidate_profiles', [
        'user_id' => $candidate->id,
        'headline' => 'Laravel Developer',
        'location' => 'Jaipur, Rajasthan',
    ]);
});

test('candidate skills belong to the authenticated candidate profile', function () {
    $candidate = User::factory()->candidate()->create();
    $otherCandidate = User::factory()->candidate()->create();

    $this->actingAs($candidate)->post(route('candidate.skills.store'), ['name' => 'Laravel'])->assertRedirect();
    $this->actingAs($otherCandidate)->post(route('candidate.skills.store'), ['name' => 'PHP'])->assertRedirect();

    expect($candidate->candidateProfile()->firstOrFail()->skills->pluck('name')->all())->toBe(['Laravel'])
        ->and($otherCandidate->candidateProfile()->firstOrFail()->skills->pluck('name')->all())->toBe(['PHP']);
});

test('employers cannot access candidate profile management', function () {
    $employer = User::factory()->employer()->create();

    $this->actingAs($employer)->get(route('candidate.profile.edit'))->assertForbidden();
});
