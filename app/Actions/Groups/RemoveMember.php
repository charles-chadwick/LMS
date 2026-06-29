<?php

namespace App\Actions\Groups;

use App\Models\Group;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class RemoveMember
{
    use AsAction;

    /**
     * Remove a user from the group.
     */
    public function execute(Group $group, User $user): void
    {
        $group->users()->detach($user);
    }
}
