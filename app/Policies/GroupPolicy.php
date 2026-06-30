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
     * Only admins may view a group.
     */
    public function view(User $user, Group $group): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Only admins may create groups.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Only admins may update groups.
     */
    public function update(User $user, Group $group): bool
    {
        return $user->hasRole(UserRole::Admin);
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
}
