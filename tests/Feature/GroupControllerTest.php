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
    Group::factory()->general()->create();
    Group::factory()->private()->create();

    $response = $this->get(route('groups.index', ['type' => GroupType::General->value]));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('groups.data', 1)
        ->where('groups.data.0.type', GroupType::General->value)
    );
});

it('creates a group', function () {
    $response = $this->post(route('groups.store'), [
        'type' => GroupType::General->value,
        'name' => 'Cohort A',
        'description' => 'The first student cohort.',
    ]);

    $group = Group::firstWhere('name', 'Cohort A');

    expect($group)->not->toBeNull()
        ->and($group->type)->toBe(GroupType::General)
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

it('shows a group with its members', function () {
    $group = Group::factory()->general()->create();
    $student = userWithRole(UserRole::Student);
    $group->users()->attach($student, ['is_leader' => false]);

    $response = $this->get(route('groups.show', $group));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Groups/Show')
        ->where('group.id', $group->id)
        ->has('group.users', 1)
    );
});

it('searches assignable members, including instructors and students but excluding existing members', function () {
    $group = Group::factory()->general()->create();
    $member = userWithRole(UserRole::Student);
    $group->users()->attach($member, ['is_leader' => false]);

    $student = userWithRole(UserRole::Student);
    $instructor = userWithRole(UserRole::Instructor);
    $admin = userWithRole(UserRole::Admin); // ineligible, must be excluded

    $response = $this->getJson(route('groups.members.assignable', $group));

    $response->assertOk();
    $ids = collect($response->json())->pluck('id');
    expect($ids)->toContain($student->id)
        ->toContain($instructor->id)
        ->not->toContain($member->id)
        ->not->toContain($admin->id);
});

it('filters assignable members by the search term', function () {
    $group = Group::factory()->general()->create();
    $match = userWithRole(UserRole::Student);
    $match->update(['first_name' => 'Searchable', 'last_name' => 'Member']);
    userWithRole(UserRole::Student); // noise, should not match

    $response = $this->getJson(route('groups.members.assignable', ['group' => $group, 'search' => 'Searchable']));

    $response->assertOk();
    $ids = collect($response->json())->pluck('id');
    expect($ids)->toContain($match->id)->toHaveCount(1);
});

it('forbids a non-admin from searching assignable members', function () {
    $group = Group::factory()->general()->create();

    $this->actingAs(userWithRole(UserRole::Instructor))
        ->getJson(route('groups.members.assignable', $group))
        ->assertForbidden();
});

it('updates a group', function () {
    $group = Group::factory()->general()->create();

    $response = $this->put(route('groups.update', $group), [
        'type' => GroupType::General->value,
        'name' => 'Renamed Group',
        'description' => 'Updated description.',
    ]);

    $group->refresh();

    expect($group->name)->toBe('Renamed Group')
        ->and($group->type)->toBe(GroupType::General);
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

it('adds an instructor or student as a member', function () {
    $group = Group::factory()->general()->create();
    $student = userWithRole(UserRole::Student);

    $response = $this->post(route('groups.members.store', $group), [
        'user_id' => $student->id,
        'is_leader' => true,
    ]);

    $response->assertRedirect(route('groups.show', $group));
    expect($group->users()->whereKey($student->id)->exists())->toBeTrue()
        ->and($group->leaders()->whereKey($student->id)->exists())->toBeTrue();
});

it('rejects a member who is neither an instructor nor a student', function () {
    $group = Group::factory()->general()->create();
    $admin = userWithRole(UserRole::Admin);

    $response = $this->post(route('groups.members.store', $group), [
        'user_id' => $admin->id,
    ]);

    $response->assertSessionHasErrors('user_id');
    expect($group->users()->count())->toBe(0);
});

it('rejects a duplicate member', function () {
    $group = Group::factory()->general()->create();
    $student = userWithRole(UserRole::Student);
    $group->users()->attach($student, ['is_leader' => false]);

    $response = $this->post(route('groups.members.store', $group), [
        'user_id' => $student->id,
    ]);

    $response->assertSessionHasErrors('user_id');
});

it('toggles a member leadership status', function () {
    $group = Group::factory()->general()->create();
    $student = userWithRole(UserRole::Student);
    $group->users()->attach($student, ['is_leader' => false]);

    $response = $this->put(route('groups.members.update', ['group' => $group, 'user' => $student]), [
        'is_leader' => true,
    ]);

    $response->assertRedirect(route('groups.show', $group));
    expect($group->leaders()->whereKey($student->id)->exists())->toBeTrue();
});

it('removes a member', function () {
    $group = Group::factory()->general()->create();
    $student = userWithRole(UserRole::Student);
    $group->users()->attach($student, ['is_leader' => false]);

    $response = $this->delete(route('groups.members.destroy', ['group' => $group, 'user' => $student]));

    $response->assertRedirect(route('groups.show', $group));
    expect($group->users()->whereKey($student->id)->exists())->toBeFalse();
});
