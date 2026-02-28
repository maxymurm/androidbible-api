<?php

namespace App\Policies;

use App\Models\ProgressMark;
use App\Models\User;

class ProgressMarkPolicy
{
    public function view(User $user, ProgressMark $progressMark): bool
    {
        return $user->id === $progressMark->user_id;
    }

    public function update(User $user, ProgressMark $progressMark): bool
    {
        return $user->id === $progressMark->user_id;
    }
}
