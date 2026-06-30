<?php

use App\Enums\UserRole;
use App\Models\Group;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(LazilyRefreshDatabase::class);

it('exposes user and group management flags to admins', function () {
    $this->actingAs(userWithRole(UserRole::Admin))
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.can.view_users', true)
            ->where('auth.can.manage_groups', true)
        );
});

it('hides user and group management from a plain student', function () {
    $this->actingAs(userWithRole(UserRole::Student))
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.can.view_users', false)
            ->where('auth.can.manage_groups', false)
        );
});

it('hides group management from an instructor who leads no group', function () {
    $this->actingAs(userWithRole(UserRole::Instructor))
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.can.manage_groups', false)
        );
});

it('shows group management to an instructor who leads a group', function () {
    $instructor = userWithRole(UserRole::Instructor);
    $group = Group::factory()->create();
    $group->users()->attach($instructor, ['is_leader' => true]);

    $this->actingAs($instructor)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.can.manage_groups', true)
        );
});

it('does not treat a non-leader group member as a group manager', function () {
    $member = userWithRole(UserRole::Student);
    $group = Group::factory()->create();
    $group->users()->attach($member, ['is_leader' => false]);

    $this->actingAs($member)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.can.manage_groups', false)
        );
});
