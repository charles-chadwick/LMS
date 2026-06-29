<?php

namespace App\Actions\Groups;

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class ListAssignableUsers
{
    use AsAction;

    /**
     * List users whose role matches the group type and who are not yet members.
     *
     * @return Collection<int, User>
     */
    public function execute(Group $group): Collection
    {
        $member_ids = $group->users()->pluck('users.id');

        return User::whereHas('roles', fn ($query) => $query->where('name', $group->type->toUserRole()->value))
            ->whereNotIn('id', $member_ids)
            ->with('media')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email']);
    }
}
