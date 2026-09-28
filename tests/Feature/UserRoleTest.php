<?php

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('UserRole enum has expected values', function () {
    expect(UserRole::Owner->value)->toBe('owner')
        ->and(UserRole::Admin->value)->toBe('admin')
        ->and(UserRole::Candidate->value)->toBe('candidate')
        ->and(UserRole::Employer->value)->toBe('employer');
});

test('new user defaults to candidate role and casts to UserRole enum', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe(UserRole::Candidate)
        ->and($user->isCandidate())->toBeTrue()
        ->and($user->isEmployer())->toBeFalse()
        ->and($user->isAdmin())->toBeFalse()
        ->and($user->isOwner())->toBeFalse();
});

test('user helper methods reflect assigned role enum', function () {
    $owner = User::factory()->owner()->create();
    expect($owner->role)->toBe(UserRole::Owner)
        ->and($owner->isOwner())->toBeTrue()
        ->and($owner->isAdmin())->toBeFalse();

    $admin = User::factory()->admin()->create();
    expect($admin->role)->toBe(UserRole::Admin)
        ->and($admin->isAdmin())->toBeTrue()
        ->and($admin->isOwner())->toBeFalse();

    $employer = User::factory()->employer()->create();
    expect($employer->role)->toBe(UserRole::Employer)
        ->and($employer->isEmployer())->toBeTrue()
        ->and($employer->isCandidate())->toBeFalse();
});

test('role cannot be mass-assigned through constructor or fill', function () {
    $user = new User([
        'name' => 'Attempted Admin',
        'email' => 'hacker@example.com',
        'password' => 'secret123',
        'role' => 'admin',
    ]);

    // Role is guarded, so mass-assignment ignores it and it is not set on attribute array until explicitly set or saved to db
    expect($user->getAttributes())->not->toHaveKey('role');

    $user->save();
    $user->refresh();

    // Database default kicks in
    expect($user->role)->toBe(UserRole::Candidate)
        ->and($user->isCandidate())->toBeTrue()
        ->and($user->isAdmin())->toBeFalse();
});
