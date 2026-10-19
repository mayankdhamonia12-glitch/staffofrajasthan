<?php

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

test('unverified accounts are redirected from each role dashboard to email verification', function (UserRole $role, string $route) {
    $user = User::factory()->unverified()->create(['role' => $role]);

    $this->actingAs($user)->get(route($route))->assertRedirect(route('verification.notice'));
})->with([
    [UserRole::Candidate, 'candidate.dashboard'],
    [UserRole::Employer, 'employer.dashboard'],
    [UserRole::Admin, 'admin.dashboard'],
    [UserRole::Owner, 'admin.dashboard'],
]);

test('unverified candidates cannot apply or change private candidate settings', function () {
    $candidate = User::factory()->unverified()->candidate()->create();

    $this->actingAs($candidate)->get(route('candidate.settings.edit'))->assertRedirect(route('verification.notice'));
    $this->patch(route('candidate.settings.update'), ['name' => 'Forged Update'])->assertRedirect(route('verification.notice'));
});
