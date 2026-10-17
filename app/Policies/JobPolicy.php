<?php

namespace App\Policies;

use App\Models\Job;
use App\Models\User;

class JobPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isSuspended() && ($user->isAdmin() || $user->isOwner());
    }

    public function view(User $user, Job $job): bool
    {
        return $this->viewAny($user);
    }

    public function moderate(User $user, Job $job): bool
    {
        return $this->view($user, $job);
    }
}
