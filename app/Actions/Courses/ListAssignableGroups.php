<?php

namespace App\Actions\Courses;

use App\Models\Group;
use Illuminate\Support\Collection;

class ListAssignableGroups
{
    /**
     * List groups whose members can be bulk-enrolled, optionally filtered by a
     * name/description search term and capped for a typeahead.
     *
     * Assignment is not persisted, so every group is always selectable.
     *
     * @return Collection<int, Group>
     */
    public function execute(?string $search = null, int $limit = 20): Collection
    {
        return Group::when($search, fn ($query, $term) => $query->where(fn ($builder) => $builder
            ->where('name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%")))
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'description']);
    }
}
