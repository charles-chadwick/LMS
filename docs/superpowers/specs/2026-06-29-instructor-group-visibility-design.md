# Member-Based Group Visibility — Design

**Date:** 2026-06-29
**Branch:** feature/bulk-enroll-group-into-course (work to be done on a dedicated branch)

## Problem

Course and group visibility are asymmetric for non-admin users:

- **Courses** are assignment-scoped via `Course::scopeVisibleTo`. Any non-admin sees only
  the courses they have a live `courses_users` row for (as instructor *or* student), and
  instructors can manage the courses they teach (`CoursePolicy::teaches`).
- **Groups** are admin-only. `GroupPolicy` gates every method on `UserRole::Admin`, and
  `GroupController::index` calls `authorize('viewAny')`, so any non-admin (including
  instructors) receives a 403 on the group index. There is no group visibility scope.

## Goal

Make groups behave like courses:

- Any **non-admin** user sees groups they are a member of (role-agnostic, mirroring the
  course scope which ignores `is_instructor`).
- **Instructors** can additionally *manage* (update + manage-members) groups they **lead**
  (`group_users.is_leader = true`), mirroring `CoursePolicy::teaches`.
- **Admins** keep full access to all groups.

### Explicit non-goals (approved defaults)

- `create`, `delete`, `restore`, `forceDelete` on groups stay **admin-only**. Group leaders
  can edit and manage members, but cannot create or delete entire groups. This is the one
  intentional divergence from courses, where `delete` follows `update`.

## Components

### 1. `Group::scopeVisibleTo` (new)

Mirror `Course::scopeVisibleTo` (`app/Models/Course.php:95-107`). `group_users` carries a
`deleted_at` column, so the soft-delete guard matches the course pattern.

```php
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

Add `Illuminate\Database\Eloquent\Builder` and `App\Enums\UserRole` imports to the model.

### 2. `GroupPolicy` — adopt the course shape

- `viewAny(User $user): bool` → `return true;` (filtering moves to the scope, mirroring
  `CoursePolicy::viewAny`). The controller's `authorize('viewAny')` then passes for everyone.
- `view(User $user, Group $group): bool` →
  `Group::query()->visibleTo($user)->whereKey($group->id)->exists();`
- `update(User $user, Group $group): bool` → `Admin || leads($user, $group)`.
- `leads(User $user, Group $group): bool` (new private), mirroring `teaches()`:

  ```php
  private function leads(User $user, Group $group): bool
  {
      return $user->hasRole(UserRole::Instructor)
          && $group->leaders()->whereKey($user->id)->exists();
  }
  ```
- `manageMembers` → unchanged (already delegates to `update`).
- `create`, `delete`, `restore`, `forceDelete` → unchanged (admin-only).

### 3. `ListGroups` action

Add `->visibleTo($request->user())` to the query builder in
`app/Actions/Groups/ListGroups.php`. The per-row `can_update` / `can_delete` flags already
resolve through the policy, so they automatically reflect the new rules.

### 4. Controller / frontend

- `GroupController::index` needs no change — `viewAny` now returns `true`, so the existing
  `authorize('viewAny')` passes.
- `Groups/Index` and `Groups/Show` Vue pages already drive off `can_update` / `can_delete` /
  `can.*` flags. **Verify during implementation:** whether the index template shows a
  "Create Group" action gated on something other than an explicit permission flag. If so,
  pass a `can_create` flag (create stays admin-only).

## Data Flow

- **Index:** `GroupController::index` → `authorize('viewAny')` (passes) → `ListGroups::execute`
  applies `visibleTo($user)` → paginated, scoped list with per-row `can_*` flags.
- **Single group:** `GroupController::show` → `authorize('view', $group)` →
  `GroupPolicy::view` reuses `visibleTo`, blocking direct URLs to groups the user is not in.
- **Edit / manage members:** `authorize('update' | 'manageMembers', $group)` →
  `Admin || leads(...)`.

## Testing (TDD)

Mirror the existing course authorization tests:

- Non-admin member (instructor or student) sees only groups they belong to — index listing
  and direct `show`.
- Non-member non-admin is excluded from both index and `show` (403 on `show`).
- A soft-deleted `group_users` row does **not** grant visibility.
- An instructor who **leads** a group can `update` and `manageMembers`; an instructor who is
  a non-leader member **cannot**.
- A student member can view but never `update` / `manageMembers`.
- `create`, `delete`, `restore`, `forceDelete` remain forbidden for all non-admins.
- Admin sees and manages every group.
