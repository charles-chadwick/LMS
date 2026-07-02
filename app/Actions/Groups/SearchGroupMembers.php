<?php

namespace App\Actions\Groups;

use App\Models\Group;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchGroupMembers
{
    /**
     * Paginate the group's member roster, optionally filtered by a
     * name/email search term. Each row's pivot exposes `is_leader`.
     */
    public function execute(Group $group, ?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        return $group->users()
            ->when($search, fn ($query, $term) => $query->where(fn ($builder) => $builder
                ->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")))
            ->with('media')
            ->orderBy('first_name')
            ->paginate($perPage, ['users.id', 'users.first_name', 'users.last_name', 'users.email']);
    }
}
