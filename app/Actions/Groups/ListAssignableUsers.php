<?php

namespace App\Actions\Groups;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class ListAssignableUsers
{
    use AsAction;

    /**
     * List instructors and students who are not yet members of the group.
     *
     * @return Collection<int, User>
     */
    public function execute(Group $group): Collection
    {
        $member_ids = $group->users()->pluck('users.id');

        return User::whereHas('roles', fn ($query) => $query->whereIn('name', UserRole::values(UserRole::Instructor, UserRole::Student)))
            ->whereNotIn('id', $member_ids)
            ->with('media')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email']);
    }
}
