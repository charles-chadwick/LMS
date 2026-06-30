<?php

use App\Enums\GroupType;
use App\Enums\UserRole;
use App\Models\Group;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

it('scopes visible groups to those a non-admin belongs to', function () {
    $member = userWithRole(UserRole::Student);
    $joined = Group::factory()->create();
    $joined->users()->attach($member, ['is_leader' => false]);
    Group::factory()->create(); // group the user is not in

    $visible_ids = Group::query()->visibleTo($member)->pluck('id');

    expect($visible_ids->all())->toBe([$joined->id]);
});

it('shows an admin every group through the visibility scope', function () {
    $admin = userWithRole(UserRole::Admin);
    Group::factory()->count(3)->create();

    expect(Group::query()->visibleTo($admin)->count())->toBe(3);
});

it('excludes a group whose membership was soft deleted from the scope', function () {
    $member = userWithRole(UserRole::Student);
    $group = Group::factory()->create();
    $group->users()->attach($member, ['is_leader' => false]);
    DB::table('group_users')->where('user_id', $member->id)->update(['deleted_at' => now()]);

    expect(Group::query()->visibleTo($member)->count())->toBe(0);
});

it('shows a non-admin only the groups they belong to on the index', function () {
    $instructor = userWithRole(UserRole::Instructor);
    $joined = Group::factory()->create();
    $joined->users()->attach($instructor, ['is_leader' => false]);
    Group::factory()->create(); // group the instructor is not in

    $this->actingAs($instructor)
        ->get(route('groups.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('groups.data', 1)
            ->where('groups.data.0.id', $joined->id)
        );
});

it('shows an admin every group on the index', function () {
    $admin = userWithRole(UserRole::Admin);
    Group::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('groups.index'))
        ->assertInertia(fn (Assert $page) => $page->has('groups.data', 3));
});

it('excludes a group with soft-deleted membership from the index', function () {
    $instructor = userWithRole(UserRole::Instructor);
    $group = Group::factory()->create();
    $group->users()->attach($instructor, ['is_leader' => false]);
    DB::table('group_users')->where('user_id', $instructor->id)->update(['deleted_at' => now()]);

    $this->actingAs($instructor)
        ->get(route('groups.index'))
        ->assertInertia(fn (Assert $page) => $page->has('groups.data', 0));
});

it('lets a member open a group they belong to', function () {
    $student = userWithRole(UserRole::Student);
    $group = Group::factory()->create();
    $group->users()->attach($student, ['is_leader' => false]);

    $this->actingAs($student)
        ->get(route('groups.show', $group))
        ->assertOk();
});

it('forbids a non-member from opening a group', function () {
    $instructor = userWithRole(UserRole::Instructor);
    $group = Group::factory()->create();

    $this->actingAs($instructor)
        ->get(route('groups.show', $group))
        ->assertForbidden();
});

it('lets an admin open any group', function () {
    $admin = userWithRole(UserRole::Admin);
    $group = Group::factory()->create();

    $this->actingAs($admin)
        ->get(route('groups.show', $group))
        ->assertOk();
});

it('forbids a member from opening a group whose membership was soft deleted', function () {
    $student = userWithRole(UserRole::Student);
    $group = Group::factory()->create();
    $group->users()->attach($student, ['is_leader' => false]);
    DB::table('group_users')->where('user_id', $student->id)->update(['deleted_at' => now()]);

    $this->actingAs($student)
        ->get(route('groups.show', $group))
        ->assertForbidden();
});

it('lets an instructor who leads a group update it', function () {
    $instructor = userWithRole(UserRole::Instructor);
    $group = Group::factory()->general()->create();
    $group->users()->attach($instructor, ['is_leader' => true]);

    $this->actingAs($instructor)
        ->put(route('groups.update', $group), [
            'type' => GroupType::General->value,
            'name' => 'Renamed By Leader',
            'description' => 'Edited by the group leader.',
        ])
        ->assertRedirect(route('groups.show', $group));

    expect($group->fresh()->name)->toBe('Renamed By Leader');
});

it('lets an instructor who leads a group manage its members', function () {
    $instructor = userWithRole(UserRole::Instructor);
    $group = Group::factory()->general()->create();
    $group->users()->attach($instructor, ['is_leader' => true]);

    $this->actingAs($instructor)
        ->getJson(route('groups.members.assignable', $group))
        ->assertOk();
});

it('forbids a non-leader member from updating the group', function () {
    $instructor = userWithRole(UserRole::Instructor);
    $group = Group::factory()->general()->create();
    $group->users()->attach($instructor, ['is_leader' => false]);

    $this->actingAs($instructor)
        ->put(route('groups.update', $group), [
            'type' => GroupType::General->value,
            'name' => 'Should Not Save',
            'description' => 'Not allowed.',
        ])
        ->assertForbidden();
});

it('forbids a student member from updating the group even when a leader', function () {
    $student = userWithRole(UserRole::Student);
    $group = Group::factory()->general()->create();
    $group->users()->attach($student, ['is_leader' => true]);

    $this->actingAs($student)
        ->put(route('groups.update', $group), [
            'type' => GroupType::General->value,
            'name' => 'Should Not Save',
            'description' => 'Not allowed.',
        ])
        ->assertForbidden();
});

it('forbids a group leader from deleting the group', function () {
    $instructor = userWithRole(UserRole::Instructor);
    $group = Group::factory()->create();
    $group->users()->attach($instructor, ['is_leader' => true]);

    $this->actingAs($instructor)
        ->delete(route('groups.destroy', $group))
        ->assertForbidden();
});
