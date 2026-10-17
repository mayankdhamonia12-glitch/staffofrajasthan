<?php

namespace App\Policies;

use App\Models\CompanyReview;
use App\Models\User;

class CompanyReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isSuspended() && ($user->isAdmin() || $user->isOwner());
    }

    public function moderate(User $user, CompanyReview $review): bool
    {
        return $this->viewAny($user)
            && (int) $review->company()->value('user_id') !== (int) $user->getKey();
    }
}
