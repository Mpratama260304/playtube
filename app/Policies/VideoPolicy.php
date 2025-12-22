<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Video;

class VideoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(?User $user, Video $video): bool
    {
        // Public videos can be viewed by anyone
        if ($video->visibility === 'public' && $video->status === 'published') {
            return true;
        }

        // Unlisted videos can be viewed by anyone with the link
        if ($video->visibility === 'unlisted' && $video->status === 'published') {
            return true;
        }

        // Private videos can only be viewed by owner
        if ($user && $video->user_id === $user->id) {
            return true;
        }

        // Admins can view all videos
        if ($user && $user->isAdmin()) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return !$user->is_banned;
    }

    public function update(User $user, Video $video): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $video->user_id === $user->id;
    }

    public function delete(User $user, Video $video): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $video->user_id === $user->id;
    }

    public function restore(User $user, Video $video): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Video $video): bool
    {
        return $user->isAdmin();
    }
}
