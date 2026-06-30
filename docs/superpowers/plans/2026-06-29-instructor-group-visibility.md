# Member-Based Group Visibility Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let any non-admin see the groups they belong to, and let instructors manage (update + manage-members) the groups they lead — mirroring how courses are scoped to assigned users.

**Architecture:** Add a `Group::scopeVisibleTo` query scope mirroring `Course::scopeVisibleTo`, reuse it in `GroupPolicy::view` and `ListGroups`, open `GroupPolicy::viewAny` to all authenticated users, and extend `GroupPolicy::update` (and thus `manageMembers`) to group leaders via a private `leads()` helper mirroring `CoursePolicy::teaches`. `create`/`delete`/`restore`/`forceDelete` stay admin-only.

**Tech Stack:** Laravel 13, PHP 8.4, Pest 4, MariaDB. Spatie roles via `UserRole` enum. Inertia + Vue (no frontend change required).

## Global Constraints

- No magic strings: use `App\Enums\UserRole` cases (e.g. `UserRole::Admin`), never literals. Spatie `hasRole()` accepts enum cases directly.
- Naming: `snake_case` variables, `camelCase` methods, `TitleCase` classes. Full descriptive variable names (`$query`, not `$q`).
- All control structures use curly braces; explicit return types and param type hints on every method; PHPDoc blocks over inline comments.
- Tests are Pest (`it()`/`expect()`), feature tests via HTTP routes, using factories and the `userWithRole()` helper. Do not remove existing tests without approval.
- Run `vendor/bin/pint --dirty --format agent` before committing any PHP change.
- The `group_users` pivot has a `deleted_at` column; soft-deleted membership must NOT grant visibility.

---

### Task 1: `Group::scopeVisibleTo` query scope

**Files:**
- Modify: `app/Models/Group.php`
- Test: `tests/Feature/GroupVisibilityTest.php` (create)

**Interfaces:**
- Produces: `Group::scopeVisibleTo(Builder $query, User $user): void` — usable as `Group::query()->visibleTo($user)`. Admins are unfiltered; non-admins are restricted to groups with a non-soft-deleted `group_users` row for that user.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/GroupVisibilityTest.php` with all imports this file will need across every task (later tasks add only `it()` blocks, no new `use` lines):

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/GroupVisibilityTest.php`
Expected: FAIL — `Call to undefined method ...::visibleTo()` (or `scopeVisibleTo`).

- [ ] **Step 3: Add the scope to the model**

In `app/Models/Group.php`, add imports below the existing `use` lines:

```php
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
```

Add this method to the `Group` class (after `leaders()`):

```php
    /**
     * Scope the query to groups visible to the given user.
     *
     * Admins see every group. Any other user sees only the groups they belong
     * to — a non-deleted `group_users` row, regardless of leadership status.
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->hasRole(UserRole::Admin)) {
            return;
        }

        $query->whereExists(function (\Illuminate\Database\Query\Builder $subquery) use ($user): void {
            $subquery->from('group_users')
                ->whereColumn('group_users.group_id', 'groups.id')
                ->where('group_users.user_id', $user->id)
                ->whereNull('group_users.deleted_at');
        });
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/GroupVisibilityTest.php`
Expected: PASS (3 passed).

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/Group.php tests/Feature/GroupVisibilityTest.php
git commit -m "feat: add member-based group visibility scope

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 2: Scope the group index and open `viewAny`

**Files:**
- Modify: `app/Policies/GroupPolicy.php` (`viewAny`)
- Modify: `app/Actions/Groups/ListGroups.php`
- Modify: `tests/Feature/GroupControllerTest.php` (the existing `forbids non-admins from managing groups` test now describes obsolete behavior)
- Test: `tests/Feature/GroupVisibilityTest.php` (add index cases)

**Interfaces:**
- Consumes: `Group::scopeVisibleTo` from Task 1.
- Produces: `groups.index` is reachable by any authenticated user and returns only groups visible to them.

- [ ] **Step 1: Write the failing index tests**

Append to `tests/Feature/GroupVisibilityTest.php`:

```php
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
```

- [ ] **Step 2: Run the new tests to verify they fail**

Run: `php artisan test --compact tests/Feature/GroupVisibilityTest.php`
Expected: the new index tests FAIL with 403 (`viewAny` still admin-only).

- [ ] **Step 3: Open `viewAny` to all authenticated users**

In `app/Policies/GroupPolicy.php`, replace the `viewAny` method:

```php
    /**
     * Any authenticated user may browse groups; the visibility scope filters them.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }
```

- [ ] **Step 4: Apply the visibility scope in `ListGroups`**

In `app/Actions/Groups/ListGroups.php`, change the query builder so the base query is scoped. Replace the `$query = Group::query()` chain's first line with a scoped version:

```php
        $query = Group::query()
            ->visibleTo($request->user())
            ->select([
                'id',
                'type',
                'name',
                'description',
                'created_at',
            ])
            ->withCount('users');
```

- [ ] **Step 5: Run the visibility tests to verify they pass**

Run: `php artisan test --compact tests/Feature/GroupVisibilityTest.php`
Expected: PASS (all index + scope tests green).

- [ ] **Step 6: Update the now-obsolete existing test**

In `tests/Feature/GroupControllerTest.php`, the test `forbids non-admins from managing groups` (around line 185) asserts an instructor gets 403 on `groups.index` — that behavior is intentionally changed. Replace that test with one asserting the still-forbidden action (creating a group):

```php
it('forbids non-admins from creating groups', function () {
    $instructor = userWithRole(UserRole::Instructor);

    $this->actingAs($instructor)
        ->get(route('groups.create'))
        ->assertForbidden();
});
```

- [ ] **Step 7: Run the controller test file to verify it passes**

Run: `php artisan test --compact tests/Feature/GroupControllerTest.php`
Expected: PASS (the whole file, including the rewritten test).

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Policies/GroupPolicy.php app/Actions/Groups/ListGroups.php tests/Feature/GroupVisibilityTest.php tests/Feature/GroupControllerTest.php
git commit -m "feat: scope group index to groups the user belongs to

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 3: Single-group view authorization

**Files:**
- Modify: `app/Policies/GroupPolicy.php` (`view`)
- Test: `tests/Feature/GroupVisibilityTest.php` (add show cases)

**Interfaces:**
- Consumes: `Group::scopeVisibleTo` from Task 1.
- Produces: `GroupPolicy::view(User $user, Group $group): bool` returns true only when the group is visible to the user; `groups.show` blocks direct URLs to groups a non-admin is not in.

- [ ] **Step 1: Write the failing show tests**

Append to `tests/Feature/GroupVisibilityTest.php`:

```php
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
```

- [ ] **Step 2: Run the new tests to verify they fail**

Run: `php artisan test --compact tests/Feature/GroupVisibilityTest.php`
Expected: the member/admin show tests FAIL with 403 (`view` still admin-only).

- [ ] **Step 3: Reuse the scope in `GroupPolicy::view`**

In `app/Policies/GroupPolicy.php`, replace the `view` method:

```php
    /**
     * A user may view a group only if it is visible to them.
     */
    public function view(User $user, Group $group): bool
    {
        return Group::query()->visibleTo($user)->whereKey($group->id)->exists();
    }
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php artisan test --compact tests/Feature/GroupVisibilityTest.php`
Expected: PASS (all show cases green).

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Policies/GroupPolicy.php tests/Feature/GroupVisibilityTest.php
git commit -m "feat: block direct access to groups a user does not belong to

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 4: Leader management authorization

**Files:**
- Modify: `app/Policies/GroupPolicy.php` (`update` + new private `leads`)
- Test: `tests/Feature/GroupVisibilityTest.php` (add management cases)

**Interfaces:**
- Consumes: nothing new.
- Produces: `GroupPolicy::update` returns true for admins and for instructors who lead the group (`group_users.is_leader = true`). `manageMembers` already delegates to `update`, so it inherits this. `create`/`delete`/`restore`/`forceDelete` stay admin-only.

- [ ] **Step 1: Write the failing management tests**

Append to `tests/Feature/GroupVisibilityTest.php`:

```php
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
```

- [ ] **Step 2: Run the new tests to verify they fail**

Run: `php artisan test --compact tests/Feature/GroupVisibilityTest.php`
Expected: the leader update/manage tests FAIL with 403 (`update` still admin-only); the "forbids" tests already pass.

- [ ] **Step 3: Extend `update` and add the `leads` helper**

In `app/Policies/GroupPolicy.php`, replace the `update` method and add a private `leads` method at the end of the class:

```php
    /**
     * Admins may update any group; instructors only the ones they lead.
     */
    public function update(User $user, Group $group): bool
    {
        return $user->hasRole(UserRole::Admin) || $this->leads($user, $group);
    }
```

```php
    /**
     * Determine whether the user is an instructor who leads the group.
     */
    private function leads(User $user, Group $group): bool
    {
        return $user->hasRole(UserRole::Instructor)
            && $group->leaders()->whereKey($user->id)->exists();
    }
```

(`manageMembers` already delegates to `update`; `delete`/`create`/`restore`/`forceDelete` are unchanged and stay admin-only.)

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php artisan test --compact tests/Feature/GroupVisibilityTest.php`
Expected: PASS (all management cases green).

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Policies/GroupPolicy.php tests/Feature/GroupVisibilityTest.php
git commit -m "feat: let group leaders update and manage their groups

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 5: Full-suite regression check

**Files:** none (verification only).

- [ ] **Step 1: Run the group-related suite**

Run: `php artisan test --compact tests/Feature/GroupControllerTest.php tests/Feature/GroupVisibilityTest.php tests/Feature/EnrollGroupMembersTest.php`
Expected: PASS.

- [ ] **Step 2: Run the entire test suite**

Run: `php artisan test --compact`
Expected: PASS. If anything outside groups fails, investigate before considering the work complete (the `view_groups` shared prop now resolves true for all users, which only affects nav visibility — confirm no test asserted the old admin-only behavior).
