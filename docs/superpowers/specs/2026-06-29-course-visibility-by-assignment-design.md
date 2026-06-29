# Scope Course Visibility by Assignment

**Date:** 2026-06-29
**Status:** Approved

## Problem

Every authenticated user currently sees every course. `ListCourses::execute()`
returns all courses to everyone and only annotates each row with per-user
capability flags (`can_take`, `can_update`, `progress_percent`).
`CoursePolicy::viewAny()` and `CoursePolicy::view()` both return `true`, and
`CourseController::show()` has no `authorize()` call — so a student can both list
all courses and open any course's detail page by guessing its URL.

A student should only see courses they are assigned to. By extension, an
instructor should only see courses they teach. Admins are unaffected.

## Definitions

- **Assignment** is a row in the `courses_users` pivot with `deleted_at IS NULL`.
  `is_instructor = false` is a student enrollment; `is_instructor = true` is an
  instructor assignment.
- **Role** checks use Spatie's `hasRole()` (consistent with
  `ListCourses.php:41` and `CoursePolicy`), e.g. `$user->hasRole(UserRole::Admin)`.

## Core Rule (single source of truth)

A non-admin may see a course only if they have a non-deleted `courses_users` row
for it (any `is_instructor` value). This single predicate satisfies both
requirements:

- Students see courses they are enrolled in (`is_instructor = false`).
- Instructors see courses they teach (`is_instructor = true`).
- A user who is both an instructor of some courses and a student of others sees
  the union — every course they are assigned to.

Admins are unrestricted.

The predicate is defined in exactly one place — a `Course` query scope — and the
policy reuses it, so list filtering and direct-access authorization can never
drift apart.

## Components

### 1. `Course::scopeVisibleTo(Builder $query, User $user): void` (new)

Added to `app/Models/Course.php`.

- If `$user->hasRole(UserRole::Admin)` → no-op (admin sees all).
- Otherwise → restrict to courses having a matching `courses_users` row via
  `whereExists`: `course_id` equals the course, `user_id` equals the user, and
  `deleted_at IS NULL`. No filter on `is_instructor`.

This is the only place the visibility predicate lives.

### 2. `ListCourses::execute()` (modified)

`app/Actions/Courses/ListCourses.php`. Apply `->visibleTo($user)` to the base
query before pagination. The existing annotation logic (`can_take`,
`can_update`, `progress_percent`, the admin/taught/enrolled id collections) is
unchanged — it still decorates whatever rows survive the scope.

### 3. `CoursePolicy::view()` (modified)

`app/Policies/CoursePolicy.php`. Change the body from `return true` to reuse the
scope as the single source of truth:

```php
return Course::query()->visibleTo($user)->whereKey($course->id)->exists();
```

For an admin the scope is a no-op, so this returns `true` whenever the course
exists. `viewAny()` stays `true` — every authenticated user may reach the (now
filtered) index.

### 4. `CourseController::show()` (modified)

`app/Http/Controllers/CourseController.php`. Add `$this->authorize('view', $course);`
at the top of `show()` so direct/deep-link access to an unassigned course is
rejected with a 403.

## Out of Scope (no change)

- `CourseLearnController` and `CourseCertificateController` already gate on the
  `take` and `viewCertificate` policies, which require enrollment.
- `Courses/Index.vue` renders whatever `courses` it is given; filtering happens
  server-side, so no frontend change is required.
- The redundant `role` column vs. Spatie `roles` pivot inconsistency is
  pre-existing and not addressed here.

## Testing

Feature tests (Pest) using the model factories and the `AssignStudent` /
`AssignInstructor` actions (or pivot attach) for setup:

- **Index — admin** sees all courses.
- **Index — instructor** sees only courses they teach, not others.
- **Index — student** sees only courses they are enrolled in, not others.
- **Index — soft-deleted enrollment** is excluded (student whose
  `courses_users` row is soft-deleted does not see that course).
- **Show — student, assigned** → 200.
- **Show — student, unassigned** → 403.
- **Show — admin** → 200 for any course.

Run the affected tests with a filter, then offer to run the full suite.
