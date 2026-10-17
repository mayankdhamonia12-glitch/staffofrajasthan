<?php

namespace App\Services;

use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountManagementService
{
    public function __construct(private readonly AdminAuditLogger $audit) {}

    public function suspend(User $actor, User $target, string $reason): User
    {
        return DB::transaction(function () use ($actor, $target, $reason): User {
            $locked = User::query()->lockForUpdate()->whereKey($target->getKey())->firstOrFail();
            $this->protectOwner($locked);

            if ($locked->isSuspended()) {
                return $locked;
            }

            $before = ['is_suspended' => false, 'role' => $locked->role->value];
            $locked->forceFill([
                'is_suspended' => true,
                'suspended_at' => now(),
                'suspended_by_user_id' => $actor->getKey(),
                'suspension_reason' => trim($reason),
            ])->save();

            $this->audit->record($actor, 'user.suspended', $locked, 'Suspended account '.$locked->getKey().'.', $reason, $before, [
                'is_suspended' => true,
                'role' => $locked->role->value,
            ]);

            return $locked;
        });
    }

    public function reactivate(User $actor, User $target, string $reason): User
    {
        return DB::transaction(function () use ($actor, $target, $reason): User {
            $locked = User::query()->lockForUpdate()->whereKey($target->getKey())->firstOrFail();
            $this->protectOwner($locked);

            if (! $locked->isSuspended()) {
                return $locked;
            }

            $before = ['is_suspended' => true, 'role' => $locked->role->value];
            $locked->forceFill([
                'is_suspended' => false,
                'suspended_at' => null,
                'suspended_by_user_id' => null,
                'suspension_reason' => null,
            ])->save();

            $this->audit->record($actor, 'user.reactivated', $locked, 'Reactivated account '.$locked->getKey().'.', $reason, $before, [
                'is_suspended' => false,
                'role' => $locked->role->value,
            ]);

            return $locked;
        });
    }

    public function changeRole(User $actor, User $target, UserRole $newRole): User
    {
        if (! in_array($newRole, [UserRole::Admin, UserRole::Candidate, UserRole::Employer], true)) {
            throw ValidationException::withMessages(['role' => 'Owner privileges cannot be assigned through this workflow.']);
        }

        return DB::transaction(function () use ($actor, $target, $newRole): User {
            $locked = User::query()->lockForUpdate()->whereKey($target->getKey())->firstOrFail();
            $this->protectOwner($locked);

            if ((int) $actor->getKey() === (int) $locked->getKey()) {
                throw ValidationException::withMessages(['user' => 'You cannot change your own role.']);
            }

            $previousRole = $locked->role;

            if ($previousRole === $newRole) {
                return $locked;
            }

            $locked->forceFill(['role' => $newRole])->save();
            $this->audit->record($actor, 'user.role_changed', $locked, 'Changed account '.$locked->getKey().' role from '.$previousRole->value.' to '.$newRole->value.'.', null, [
                'role' => $previousRole->value,
            ], [
                'role' => $newRole->value,
            ]);

            return $locked;
        });
    }

    private function protectOwner(User $target): void
    {
        if (! $target->isOwner()) {
            return;
        }

        $activeOwners = User::query()
            ->where('role', UserRole::Owner->value)
            ->where('is_suspended', false)
            ->lockForUpdate()
            ->count();

        if ($activeOwners <= 1) {
            throw ValidationException::withMessages(['user' => 'The last active Owner account cannot be suspended or demoted.']);
        }

        abort(403, 'Owner accounts cannot be changed through user management.');
    }
}
