<?php

namespace App\Actions\Groups;

use App\Models\Group;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateMember
{
    use AsAction;

    /**
     * Update a member's leadership status within the group.
     */
    public function execute(Group $group, User $user, bool $is_leader): void
    {
        $group->users()->updateExistingPivot($user->id, ['is_leader' => $is_leader]);
    }
}
