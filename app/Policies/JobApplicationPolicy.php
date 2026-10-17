<?php

namespace App\Policies;

use App\Models\JobApplication;
use App\Models\User;

class JobApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isSuspended() && ($user->isAdmin() || $user->isOwner());
    }

    public function view(User $user, JobApplication $application): bool
    {
        return $this->viewAny($user);
    }
}
