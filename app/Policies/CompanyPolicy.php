<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isSuspended() && ($user->isAdmin() || $user->isOwner());
    }

    public function view(User $user, Company $company): bool
    {
        return $this->viewAny($user);
    }

    public function moderate(User $user, Company $company): bool
    {
        return $this->view($user, $company);
    }
}
