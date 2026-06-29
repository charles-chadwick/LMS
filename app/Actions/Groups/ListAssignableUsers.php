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
     * List instructors and students who are not yet members of the group,
     * optionally filtered by a name/email search term and capped for a typeahead.
     *
     * @return Collection<int, User>
     */
    public function execute(Group $group, ?string $search = null, int $limit = 20): Collection
    {
        return User::whereHas('roles', fn ($query) => $query->whereIn('name', UserRole::values(UserRole::Instructor, UserRole::Student)))
            ->whereNotIn('id', fn ($query) => $query
                ->select('user_id')
                ->from('group_users')
                ->where('group_id', $group->getKey())
                ->whereNull('deleted_at'))
            ->when($search, fn ($query, $term) => $query->where(fn ($builder) => $builder
                ->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")))
            ->with('media')
            ->orderBy('first_name')
            ->limit($limit)
            ->get(['id', 'first_name', 'last_name', 'email']);
    }
}
