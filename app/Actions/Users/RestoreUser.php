<?php

namespace App\Actions\Users;

use App\Models\User;

class RestoreUser
{
    /**
     * Restore a soft-deleted user by their identifier.
     */
    public function execute(int $user_id): User
    {
        $user = User::withTrashed()->findOrFail($user_id);

        $user->restore();

        return $user;
    }
}
