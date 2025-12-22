<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Comment $comment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return !$user->is_banned;
    }

    public function update(User $user, Comment $comment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $comment->user_id === $user->id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Owner of comment can delete
        if ($comment->user_id === $user->id) {
            return true;
        }

        // Owner of video can delete comments on their video
        if ($comment->video->user_id === $user->id) {
            return true;
        }

        return false;
    }
}
