<?php

namespace App\Policies;

use App\Models\Playlist;
use App\Models\User;

class PlaylistPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(?User $user, Playlist $playlist): bool
    {
        if ($playlist->visibility === 'public') {
            return true;
        }

        if ($user && $playlist->user_id === $user->id) {
            return true;
        }

        if ($user && $user->isAdmin()) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return !$user->is_banned;
    }

    public function update(User $user, Playlist $playlist): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $playlist->user_id === $user->id;
    }

    public function delete(User $user, Playlist $playlist): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $playlist->user_id === $user->id;
    }
}
