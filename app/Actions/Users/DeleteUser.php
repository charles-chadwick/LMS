<?php

namespace App\Actions\Users;

use App\Models\User;

class DeleteUser
{
    /**
     * Soft delete a user and return their full name for messaging.
     */
    public function execute(User $user): string
    {
        $full_name = $user->full_name;

        $user->delete();

        return $full_name;
    }
}
