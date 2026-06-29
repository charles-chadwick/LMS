<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    /**
     * Only admins may browse the user directory.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Admins may view any user; everyone may view their own profile.
     */
    public function view(User $user, User $model): bool
    {
        return $user->hasRole(UserRole::Admin) || $user->is($model);
    }

    /**
     * Only admins may create users.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Only admins may update users.
     */
    public function update(User $user, User $model): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Admins may delete any user other than themselves.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->hasRole(UserRole::Admin) && ! $user->is($model);
    }

    /**
     * Only admins may restore users.
     */
    public function restore(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Only admins may permanently delete users.
     */
    public function forceDelete(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}
