<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    /**
     * Any authenticated user may browse groups; the visibility scope filters them.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * A user may view a group only if it is visible to them.
     */
    public function view(User $user, Group $group): bool
    {
        return Group::query()->visibleTo($user)->whereKey($group->id)->exists();
    }

    /**
     * Only admins may create groups.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Admins may update any group; instructors only the ones they lead.
     */
    public function update(User $user, Group $group): bool
    {
        return $user->hasRole(UserRole::Admin) || $this->leads($user, $group);
    }

    /**
     * Managing members follows the same rule as updating.
     */
    public function manageMembers(User $user, Group $group): bool
    {
        return $this->update($user, $group);
    }

    /**
     * Only admins may delete groups.
     */
    public function delete(User $user, Group $group): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Only admins may restore groups.
     */
    public function restore(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Only admins may permanently delete groups.
     */
    public function forceDelete(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Determine whether the user is an instructor who leads the group.
     */
    private function leads(User $user, Group $group): bool
    {
        return $user->hasRole(UserRole::Instructor)
            && $group->leaders()->whereKey($user->id)->exists();
    }
}
