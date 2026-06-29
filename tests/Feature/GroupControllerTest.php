<?php

use App\Enums\GroupType;
use App\Enums\UserRole;
use App\Models\Group;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->admin = userWithRole(UserRole::Admin);
    $this->actingAs($this->admin);
});

it('lists groups with member counts on the index', function () {
    Group::factory()->count(2)->create();

    $response = $this->get(route('groups.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Groups/Index')
        ->has('groups.data')
        ->has('groups.data.0.users_count')
    );
});

it('filters groups by search term', function () {
    Group::factory()->create(['name' => 'Morning Cohort']);
    Group::factory()->create(['name' => 'Evening Cohort']);

    $response = $this->get(route('groups.index', ['search' => 'Morning']));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('groups.data', 1)
        ->where('groups.data.0.name', 'Morning Cohort')
    );
});

it('filters groups by type', function () {
    Group::factory()->instructors()->create();
    Group::factory()->students()->create();

    $response = $this->get(route('groups.index', ['type' => GroupType::Instructor->value]));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('groups.data', 1)
        ->where('groups.data.0.type', GroupType::Instructor->value)
    );
});

it('creates a group', function () {
    $response = $this->post(route('groups.store'), [
        'type' => GroupType::Student->value,
        'name' => 'Cohort A',
        'description' => 'The first student cohort.',
    ]);

    $group = Group::firstWhere('name', 'Cohort A');

    expect($group)->not->toBeNull()
        ->and($group->type)->toBe(GroupType::Student)
        ->and($group->description)->toBe('The first student cohort.');
    $response->assertRedirect(route('groups.show', $group));
    $response->assertSessionHas('success');
});

it('validates required fields when creating a group', function () {
    $response = $this->post(route('groups.store'), []);

    $response->assertSessionHasErrors(['type', 'name', 'description']);
});

it('rejects an invalid type when creating a group', function () {
    $response = $this->post(route('groups.store'), [
        'type' => 'Admin',
        'name' => 'Bad Group',
        'description' => 'Nope.',
    ]);

    $response->assertSessionHasErrors('type');
});

it('shows a group with its members and assignable users', function () {
    $group = Group::factory()->students()->create();
    $student = userWithRole(UserRole::Student);
    $group->users()->attach($student, ['is_leader' => false]);

    $other_student = userWithRole(UserRole::Student);

    $response = $this->get(route('groups.show', $group));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Groups/Show')
        ->where('group.id', $group->id)
        ->has('group.users', 1)
        ->has('assignable_users', 1)
        ->where('assignable_users.0.id', $other_student->id)
    );
});

it('updates a group', function () {
    $group = Group::factory()->students()->create();

    $response = $this->put(route('groups.update', $group), [
        'type' => GroupType::Instructor->value,
        'name' => 'Renamed Group',
        'description' => 'Updated description.',
    ]);

    $group->refresh();

    expect($group->name)->toBe('Renamed Group')
        ->and($group->type)->toBe(GroupType::Instructor);
    $response->assertRedirect(route('groups.show', $group));
});

it('soft deletes a group', function () {
    $group = Group::factory()->create();

    $response = $this->delete(route('groups.destroy', $group));

    $response->assertRedirect(route('groups.index'));
    expect($group->fresh()->trashed())->toBeTrue();
});

it('restores a soft deleted group', function () {
    $group = Group::factory()->create();
    $group->delete();

    $response = $this->post(route('groups.restore', $group->id));

    $response->assertRedirect(route('groups.show', $group));
    expect($group->fresh()->trashed())->toBeFalse();
});

it('permanently deletes a group', function () {
    $group = Group::factory()->create();
    $group->delete();

    $response = $this->delete(route('groups.forceDestroy', $group->id));

    $response->assertRedirect(route('groups.index'));
    expect(Group::withTrashed()->find($group->id))->toBeNull();
});

it('forbids non-admins from managing groups', function () {
    $instructor = userWithRole(UserRole::Instructor);

    $this->actingAs($instructor)
        ->get(route('groups.index'))
        ->assertForbidden();
});

it('adds a member whose role matches the group type', function () {
    $group = Group::factory()->students()->create();
    $student = userWithRole(UserRole::Student);

    $response = $this->post(route('groups.members.store', $group), [
        'user_id' => $student->id,
        'is_leader' => true,
    ]);

    $response->assertRedirect(route('groups.show', $group));
    expect($group->users()->whereKey($student->id)->exists())->toBeTrue()
        ->and($group->leaders()->whereKey($student->id)->exists())->toBeTrue();
});

it('rejects a member whose role does not match the group type', function () {
    $group = Group::factory()->students()->create();
    $instructor = userWithRole(UserRole::Instructor);

    $response = $this->post(route('groups.members.store', $group), [
        'user_id' => $instructor->id,
    ]);

    $response->assertSessionHasErrors('user_id');
    expect($group->users()->count())->toBe(0);
});

it('rejects a duplicate member', function () {
    $group = Group::factory()->students()->create();
    $student = userWithRole(UserRole::Student);
    $group->users()->attach($student, ['is_leader' => false]);

    $response = $this->post(route('groups.members.store', $group), [
        'user_id' => $student->id,
    ]);

    $response->assertSessionHasErrors('user_id');
});

it('toggles a member leadership status', function () {
    $group = Group::factory()->students()->create();
    $student = userWithRole(UserRole::Student);
    $group->users()->attach($student, ['is_leader' => false]);

    $response = $this->put(route('groups.members.update', ['group' => $group, 'user' => $student]), [
        'is_leader' => true,
    ]);

    $response->assertRedirect(route('groups.show', $group));
    expect($group->leaders()->whereKey($student->id)->exists())->toBeTrue();
});

it('removes a member', function () {
    $group = Group::factory()->students()->create();
    $student = userWithRole(UserRole::Student);
    $group->users()->attach($student, ['is_leader' => false]);

    $response = $this->delete(route('groups.members.destroy', ['group' => $group, 'user' => $student]));

    $response->assertRedirect(route('groups.show', $group));
    expect($group->users()->whereKey($student->id)->exists())->toBeFalse();
});
