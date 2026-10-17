<?php

namespace App\Policies;

use App\Models\User;
use App\UserRole;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin() || $actor->isOwner();
    }

    public function view(User $actor, User $subject): bool
    {
        if ($actor->isOwner()) {
            return true;
        }

        return $actor->isAdmin() && in_array($subject->role, [UserRole::Candidate, UserRole::Employer], true);
    }

    public function suspend(User $actor, User $subject): bool
    {
        if ($actor->isSuspended() || (int) $actor->getKey() === (int) $subject->getKey() || $subject->isOwner()) {
            return false;
        }

        if ($actor->isOwner()) {
            return true;
        }

        return $actor->isAdmin() && in_array($subject->role, [UserRole::Candidate, UserRole::Employer], true);
    }

    public function reactivate(User $actor, User $subject): bool
    {
        return $this->suspend($actor, $subject);
    }

    public function changeRole(User $actor, User $subject): bool
    {
        return $actor->isOwner()
            && ! $actor->isSuspended()
            && (int) $actor->getKey() !== (int) $subject->getKey()
            && ! $subject->isOwner();
    }
}
