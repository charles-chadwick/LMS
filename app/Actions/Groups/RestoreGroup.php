<?php

namespace App\Actions\Groups;

use App\Models\Group;
use Lorisleiva\Actions\Concerns\AsAction;

class RestoreGroup
{
    use AsAction;

    /**
     * Restore a soft-deleted group by its identifier.
     */
    public function execute(int $group_id): Group
    {
        $group = Group::withTrashed()->findOrFail($group_id);

        $group->restore();

        return $group;
    }
}
