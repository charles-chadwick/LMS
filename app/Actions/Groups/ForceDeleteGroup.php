<?php

namespace App\Actions\Groups;

use App\Models\Group;
use Lorisleiva\Actions\Concerns\AsAction;

class ForceDeleteGroup
{
    use AsAction;

    /**
     * Permanently delete a group and return its name for messaging.
     */
    public function execute(int $group_id): string
    {
        $group = Group::withTrashed()->findOrFail($group_id);

        $name = $group->name;

        $group->forceDelete();

        return $name;
    }
}
