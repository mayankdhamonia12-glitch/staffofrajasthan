<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOwner();
    }

    public function view(User $user, Article $article): bool
    {
        return $user->isOwner() || ($user->isAdmin() && (int) $article->author_id === (int) $user->getKey());
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isOwner();
    }

    public function update(User $user, Article $article): bool
    {
        return $this->view($user, $article);
    }

    public function delete(User $user, Article $article): bool
    {
        return $this->view($user, $article);
    }
}
