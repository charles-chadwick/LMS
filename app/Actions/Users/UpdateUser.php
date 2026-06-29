<?php

namespace App\Actions\Users;

use App\Enums\UserRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UpdateUser
{
    /**
     * Update an existing user, syncing their role and only changing the
     * password when a new one was supplied.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(User $user, array $attributes): User
    {
        if (blank($attributes['password'] ?? null)) {
            unset($attributes['password']);
        }

        $user->update($attributes);

        if (array_key_exists('role', $attributes)) {
            $this->syncRole($user, $attributes['role']);
        }

        return $user;
    }

    /**
     * Keep the Spatie role assignment in step with the role column.
     */
    private function syncRole(User $user, UserRole|string $role): void
    {
        $role_value = $role instanceof UserRole ? $role->value : $role;

        Role::findOrCreate($role_value, 'web');

        $user->syncRoles([$role_value]);
    }
}
