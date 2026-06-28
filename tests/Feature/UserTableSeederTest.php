<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserTableSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(UserTableSeeder::class);
});

it('creates the three consistent Super Admins', function () {
    $super_admins = User::query()
        ->whereIn('email', [
            'president.curtis@example.com',
            'doofus.rick@example.com',
            'slow.rick@example.com',
        ])
        ->get();

    expect($super_admins)->toHaveCount(3);

    $super_admins->each(function (User $super_admin) {
        expect($super_admin->role)->toBe(UserRole::Admin)
            ->and($super_admin->hasRole('Admin'))->toBeTrue();
    });

    expect($super_admins->map->full_name->all())
        ->toContain('President Curtis', 'Doofus Rick', 'Slow Rick');
});

it('gives every seeded user a full sized avatar with a thumbnail conversion', function () {
    User::all()->each(function (User $user) {
        $avatar = $user->getFirstMedia('avatars');

        expect($avatar)->not->toBeNull()
            ->and(file_exists($avatar->getPath()))->toBeTrue()
            ->and(file_exists($avatar->getPath('thumb')))->toBeTrue();
    });
});

it('seeds instructors and students alongside the Super Admins', function () {
    expect(User::where('role', UserRole::Instructor->value)->count())->toBeGreaterThan(0)
        ->and(User::where('role', UserRole::Student->value)->count())->toBeGreaterThan(0)
        ->and(User::where('role', UserRole::Admin->value)->count())->toBe(3);
});
