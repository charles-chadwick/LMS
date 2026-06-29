<?php

namespace App\Actions\Groups;

use App\Models\Group;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteGroup
{
    use AsAction;

    /**
     * Soft delete a group and return its name for messaging.
     */
    public function execute(Group $group): string
    {
        $name = $group->name;

        $group->delete();

        return $name;
    }
}
