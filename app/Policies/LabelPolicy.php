<?php

namespace App\Policies;

use App\Models\Label;
use App\Models\User;

class LabelPolicy
{
    public function view(User $user, Label $label): bool
    {
        return $user->id === $label->user_id;
    }

    public function update(User $user, Label $label): bool
    {
        return $user->id === $label->user_id;
    }

    public function delete(User $user, Label $label): bool
    {
        return $user->id === $label->user_id;
    }
}
