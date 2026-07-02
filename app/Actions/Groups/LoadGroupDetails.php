<?php

namespace App\Actions\Groups;

use App\Models\Group;
use Lorisleiva\Actions\Concerns\AsAction;

class LoadGroupDetails
{
    use AsAction;

    /**
     * Eager load the relationships and counts needed to display a group.
     */
    public function execute(Group $group): Group
    {
        $group->load([
            'users' => function ($query) {
                $query->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
                    ->with('media')
                    ->orderBy('first_name')
                    ->orderBy('users.id')
                    ->limit(25);
            },
        ]);

        $group->loadCount('users');

        return $group;
    }
}
