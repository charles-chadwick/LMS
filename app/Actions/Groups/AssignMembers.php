<?php

namespace App\Actions\Groups;

use App\Models\Group;
use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssignMembers
{
    /**
     * Bulk-add users to the group as non-leader members.
     *
     * @param  Collection<int, User>  $users
     * @return int The number of members attached or restored.
     */
    public function execute(Group $group, Collection $users): int
    {
        return DB::transaction(function () use ($group, $users): int {
            $added_count = 0;

            foreach ($users as $user) {
                if ($this->add($group, $user)) {
                    $added_count++;
                }
            }

            return $added_count;
        });
    }

    private function add(Group $group, User $user): bool
    {
        $membership = GroupUser::withTrashed()
            ->where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership === null) {
            $group->users()->attach($user, ['is_leader' => false]);

            return true;
        }

        if ($membership->trashed()) {
            $membership->restore();
            $membership->update(['is_leader' => false]);

            return true;
        }

        return false;
    }
}
