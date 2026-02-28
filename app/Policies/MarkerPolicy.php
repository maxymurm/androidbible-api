<?php

namespace App\Policies;

use App\Models\Marker;
use App\Models\User;

class MarkerPolicy
{
    public function view(User $user, Marker $marker): bool
    {
        return $user->id === $marker->user_id;
    }

    public function update(User $user, Marker $marker): bool
    {
        return $user->id === $marker->user_id;
    }

    public function delete(User $user, Marker $marker): bool
    {
        return $user->id === $marker->user_id;
    }
}
