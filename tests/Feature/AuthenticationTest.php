<?php

use App\Models\User;
use App\UserRole;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

test('the registration page presents separate candidate and employer journeys', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee(route('register.candidate'))
        ->assertSee(route('register.employer'));
});

test('a candidate registration always creates a candidate even with a forged role field', function () {
    Notification::fake();

    $response = $this->post(route('register.candidate.store'), [
        'name' => 'Test Candidate',
        'email' => 'candidate@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'admin',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('candidate.dashboard'));
    $user = User::where('email', 'candidate@example.com')->firstOrFail();

    expect($user->role)->toBe(UserRole::Candidate);
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('a signed verification link marks the account email as verified', function () {
    $user = User::factory()->unverified()->create();
    $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($user->getEmailForVerification()),
    ]);

    $this->actingAs($user)->get($verificationUrl)->assertRedirect(route('dashboard').'?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('an employer registration always creates an employer even with a forged role field', function () {
    $response = $this->post(route('register.employer.store'), [
        'name' => 'Test Employer',
        'email' => 'employer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'owner',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('employer.dashboard'));
    expect(User::where('email', 'employer@example.com')->firstOrFail()->role)->toBe(UserRole::Employer);
});

test('role dashboards enforce their boundaries', function () {
    $candidate = User::factory()->candidate()->create();
    $employer = User::factory()->employer()->create();
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->owner()->create();

    $this->actingAs($candidate)->get(route('candidate.dashboard'))->assertOk();
    $this->actingAs($employer)->get(route('candidate.dashboard'))->assertForbidden();
    $this->actingAs($candidate)->get(route('employer.dashboard'))->assertForbidden();
    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($owner)->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($candidate)->get(route('admin.dashboard'))->assertForbidden();
});

test('the generic dashboard redirects each role to its own workspace', function (UserRole $role, string $route) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route($route));
})->with([
    [UserRole::Candidate, 'candidate.dashboard'],
    [UserRole::Employer, 'employer.dashboard'],
    [UserRole::Admin, 'admin.dashboard'],
    [UserRole::Owner, 'admin.dashboard'],
]);
