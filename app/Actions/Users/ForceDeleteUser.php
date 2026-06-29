<?php

namespace App\Actions\Users;

use App\Models\User;

class ForceDeleteUser
{
    /**
     * Permanently delete a user and return their full name for messaging.
     */
    public function execute(int $user_id): string
    {
        $user = User::withTrashed()->findOrFail($user_id);

        $full_name = $user->full_name;

        $user->forceDelete();

        return $full_name;
    }
}
