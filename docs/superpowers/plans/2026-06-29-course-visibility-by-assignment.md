# Course Visibility by Assignment Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Restrict non-admin users so they only see courses they are assigned to — students see their enrollments, instructors see courses they teach — on both the course index list and direct (URL) access to a course's detail page.

**Architecture:** A single `Course::scopeVisibleTo($user)` query scope defines the visibility predicate (admin = all; otherwise a non-deleted `courses_users` row must exist). `ListCourses` applies the scope to filter the index, and `CoursePolicy::view()` reuses the same scope so direct access stays consistent. `CourseController::show()` is wired to enforce the `view` policy.

**Tech Stack:** Laravel 13, PHP 8.4, Eloquent, Inertia, Pest 4, Spatie Permission (roles).

## Global Constraints

- Role checks use Spatie's `hasRole()` with `UserRole` enum cases — never raw strings (e.g. `$user->hasRole(UserRole::Admin)`).
- No magic strings: use `App\Enums\UserRole`. Reference the pivot column `deleted_at` directly only in query builders (raw column, acceptable).
- Variables: `snake_case`; methods: `camelCase`; classes: `TitleCase`.
- All tests are Pest (`it()` / `expect()`), use the model factories and the `userWithRole(UserRole)` helper from `tests/Pest.php`.
- Run `vendor/bin/pint --dirty --format agent` after PHP changes, before committing.
- "Assigned" = a `courses_users` row with `deleted_at IS NULL` (any `is_instructor` value).

---

### Task 1: Scope the course index to assigned courses

**Files:**
- Modify: `app/Models/Course.php` (add `scopeVisibleTo`)
- Modify: `app/Actions/Courses/ListCourses.php:40` (apply the scope)
- Test: `tests/Feature/CourseVisibilityTest.php` (create)

**Interfaces:**
- Produces: `Course::scopeVisibleTo(\Illuminate\Database\Eloquent\Builder $query, \App\Models\User $user): void` — usable as `Course::query()->visibleTo($user)`. Admin → no-op; otherwise restricts to courses with a non-deleted `courses_users` row for `$user`.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/CourseVisibilityTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Models\Course;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

it('shows a student only the courses they are assigned to', function () {
    $student = userWithRole(UserRole::Student);
    $assigned = Course::factory()->create();
    $assigned->students()->attach($student, ['is_instructor' => false]);
    Course::factory()->create(); // unassigned course

    $this->actingAs($student)
        ->get(route('courses.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('courses.data', 1)
            ->where('courses.data.0.id', $assigned->id)
        );
});

it('shows an instructor only the courses they teach', function () {
    $instructor = userWithRole(UserRole::Instructor);
    $taught = Course::factory()->create();
    $taught->instructors()->attach($instructor, ['is_instructor' => true]);
    Course::factory()->create(); // course taught by someone else

    $this->actingAs($instructor)
        ->get(route('courses.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('courses.data', 1)
            ->where('courses.data.0.id', $taught->id)
        );
});

it('shows an admin every course', function () {
    $admin = userWithRole(UserRole::Admin);
    Course::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('courses.index'))
        ->assertInertia(fn (Assert $page) => $page->has('courses.data', 3));
});

it('excludes courses whose enrollment was soft deleted', function () {
    $student = userWithRole(UserRole::Student);
    $course = Course::factory()->create();
    $course->students()->attach($student, ['is_instructor' => false]);
    DB::table('courses_users')
        ->where('user_id', $student->id)
        ->update(['deleted_at' => now()]);

    $this->actingAs($student)
        ->get(route('courses.index'))
        ->assertInertia(fn (Assert $page) => $page->has('courses.data', 0));
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --compact tests/Feature/CourseVisibilityTest.php`
Expected: FAIL — the student/instructor/soft-delete tests see all courses (counts 2/2/1 instead of 1/1/0). `scopeVisibleTo` does not exist yet.

- [ ] **Step 3: Add the `visibleTo` scope to the Course model**

In `app/Models/Course.php`, add these imports near the top (with the other `use` statements):

```php
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
```

Add this method to the `Course` class (e.g. after the `users()` relationship):

```php
/**
 * Scope the query to courses visible to the given user.
 *
 * Admins see every course. Any other user sees only the courses they are
 * assigned to — a non-deleted `courses_users` row, whether as student or
 * instructor.
 */
public function scopeVisibleTo(Builder $query, User $user): void
{
    if ($user->hasRole(UserRole::Admin)) {
        return;
    }

    $query->whereExists(function (\Illuminate\Database\Query\Builder $subquery) use ($user): void {
        $subquery->from('courses_users')
            ->whereColumn('courses_users.course_id', 'courses.id')
            ->where('courses_users.user_id', $user->id)
            ->whereNull('courses_users.deleted_at');
    });
}
```

- [ ] **Step 4: Apply the scope in `ListCourses`**

In `app/Actions/Courses/ListCourses.php`, find the line `$user = $request->user();` (line 40) and immediately after it add:

```php
        $query->visibleTo($user);
```

So it reads:

```php
        $user = $request->user();
        $query->visibleTo($user);
        $is_admin = $user->hasRole(UserRole::Admin);
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --compact tests/Feature/CourseVisibilityTest.php`
Expected: PASS (4 passing).

- [ ] **Step 6: Run Pint and the existing course controller test**

Run: `vendor/bin/pint --dirty --format agent`
Run: `php artisan test --compact tests/Feature/CourseControllerTest.php`
Expected: Pint clean; `CourseControllerTest` still passes (its `beforeEach` acts as Admin, so the index is unfiltered there).

- [ ] **Step 7: Commit**

```bash
git add app/Models/Course.php app/Actions/Courses/ListCourses.php tests/Feature/CourseVisibilityTest.php
git commit -m "feat: scope course index to assigned courses for non-admins

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 2: Enforce assignment on direct course access

**Files:**
- Modify: `app/Policies/CoursePolicy.php:23-26` (`view`)
- Modify: `app/Http/Controllers/CourseController.php:69-79` (`show`)
- Test: `tests/Feature/CourseVisibilityTest.php` (append)

**Interfaces:**
- Consumes: `Course::scopeVisibleTo()` from Task 1.
- Produces: `CoursePolicy::view()` now returns whether the course is visible to the user; `CourseController::show()` authorizes `view` (403 otherwise).

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/CourseVisibilityTest.php`:

```php
it('lets a student open a course they are assigned to', function () {
    $student = userWithRole(UserRole::Student);
    $course = Course::factory()->create();
    $course->students()->attach($student, ['is_instructor' => false]);

    $this->actingAs($student)
        ->get(route('courses.show', $course))
        ->assertOk();
});

it('forbids a student from opening a course they are not assigned to', function () {
    $student = userWithRole(UserRole::Student);
    $course = Course::factory()->create();

    $this->actingAs($student)
        ->get(route('courses.show', $course))
        ->assertForbidden();
});

it('lets an admin open any course', function () {
    $admin = userWithRole(UserRole::Admin);
    $course = Course::factory()->create();

    $this->actingAs($admin)
        ->get(route('courses.show', $course))
        ->assertOk();
});
```

- [ ] **Step 2: Run the new tests to verify they fail**

Run: `php artisan test --compact tests/Feature/CourseVisibilityTest.php --filter="open a course"`
Expected: FAIL — the "forbids a student" test gets 200 instead of 403, because `view` returns `true` and `show()` does not authorize.

- [ ] **Step 3: Update the `view` policy to reuse the scope**

In `app/Policies/CoursePolicy.php`, replace the body of `view()`:

```php
    /**
     * A user may view a course only if it is visible to them.
     */
    public function view(User $user, Course $course): bool
    {
        return Course::query()->visibleTo($user)->whereKey($course->id)->exists();
    }
```

(Leave `viewAny()` returning `true` — every authenticated user may reach the filtered index.)

- [ ] **Step 4: Authorize `view` in the controller's `show` method**

In `app/Http/Controllers/CourseController.php`, add `$this->authorize('view', $course);` as the first line of `show()`:

```php
    public function show(Request $request, Course $course, LoadCourseDetails $loadCourseDetails): Response
    {
        $this->authorize('view', $course);

        return Inertia::render('Courses/Show', [
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --compact tests/Feature/CourseVisibilityTest.php`
Expected: PASS (7 passing — Task 1's 4 plus these 3).

- [ ] **Step 6: Run Pint and the broader course suite for regressions**

Run: `vendor/bin/pint --dirty --format agent`
Run: `php artisan test --compact tests/Feature/CourseControllerTest.php tests/Feature/CoursePolicyTakeTest.php`
Expected: Pint clean; both suites pass.

- [ ] **Step 7: Commit**

```bash
git add app/Policies/CoursePolicy.php app/Http/Controllers/CourseController.php tests/Feature/CourseVisibilityTest.php
git commit -m "feat: block direct access to unassigned courses for non-admins

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Self-Review

**Spec coverage:**
- Core rule (admin all / non-admin needs non-deleted membership, any `is_instructor`) → Task 1 Step 3 `scopeVisibleTo`. ✓
- Index filtering (`ListCourses`) → Task 1 Step 4. ✓
- `CoursePolicy::view` reuses scope; `viewAny` stays `true` → Task 2 Steps 3. ✓
- `CourseController::show` authorizes `view` → Task 2 Step 4. ✓
- Tests: admin all, instructor taught-only, student assigned-only, soft-deleted excluded, show 200 assigned / 403 unassigned / admin 200 → Task 1 Step 1 + Task 2 Step 1. ✓
- Out of scope (Learn/Certificate, Index.vue, role-column inconsistency) → untouched. ✓

**Placeholder scan:** No TBD/TODO; all code shown in full. ✓

**Type consistency:** `scopeVisibleTo(Builder $query, User $user): void` defined in Task 1, consumed verbatim as `->visibleTo($user)` in Task 1 Step 4 and `Course::query()->visibleTo($user)` in Task 2 Step 3. `User` is already imported in `Course.php`/`CoursePolicy.php` (same namespace `App\Models` for Course; `CoursePolicy` already imports `App\Models\User` and `App\Models\Course`). ✓
