<?php

namespace App\Actions\Groups;

use App\Models\Group;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateGroup
{
    use AsAction;

    /**
     * Update an existing group.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Group $group, array $attributes): Group
    {
        $group->update($attributes);

        return $group;
    }
}
