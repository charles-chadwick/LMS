<?php

namespace App\Actions\Groups;

use App\Models\Group;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class AssignMember
{
    use AsAction;

    /**
     * Add a user to the group, optionally as a leader.
     */
    public function execute(Group $group, User $user, bool $is_leader = false): void
    {
        $group->users()->attach($user, ['is_leader' => $is_leader]);
    }
}
