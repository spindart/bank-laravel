<?php

namespace App\Broadcasting;

use App\Models\User;

class PrivateUserChannel
{
    public function join(User $user, int $id): bool
    {
        return $user->id === $id;
    }
}
