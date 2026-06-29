<?php

namespace App\Actions\Users;

use App\Enums\UserRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

class CreateUser
{
    /**
     * Create a new user and sync their assigned role.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): User
    {
        $user = User::create($attributes);

        $this->syncRole($user, $attributes['role']);

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
