<?php

use App\Enums\UserRole;
use App\Models\Group;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

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
