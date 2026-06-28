# Take a Course with Progress Tracking — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let an enrolled student take a Published course by working through its published pages in order, tracking per-page completion and awarding a completion certificate.

**Architecture:** Progress is derived from `user_progress` rows (one row per completed page) — the single source of truth (Approach A). Thin controllers authorize via `CoursePolicy` and delegate to single-purpose Action classes (`LoadCoursePlayer`, `CompletePage`). A dedicated Inertia player page (`Courses/Learn`) renders one page at a time with sequential gating; a `Courses/Certificate` page renders on completion. The `courses_users` pivot gains a `completed_at` to anchor the certificate date.

**Tech Stack:** Laravel 13 (PHP 8.4), Inertia v3 + Vue 3, reka-ui components, Pest v4, Spatie roles, Ziggy routes.

## Global Constraints

- Naming: variables `snake_case`, methods `camelCase`, classes `TitleCase` (per global standards); use full descriptive names (`$query`, not `$q`).
- No magic strings: use `App\Enums\CourseStatus` (cases `Draft`, `Published`, `Archived`) for status; never literal `'Published'` in app code — use `CourseStatus::Published` (or `->value` only when a raw column comparison is required).
- Controllers stay thin: resolve Action via method injection, pass validated data to `execute()`, return `Inertia::render()` / `redirect()`. Business logic lives in `app/Actions/Courses/`.
- PHP: curly braces always; constructor property promotion; explicit return types and param type hints; PHPDoc blocks over inline comments.
- Tests: Pest only (`it()`/`expect()`); `uses(LazilyRefreshDatabase::class);` per file; use factories and the `userWithRole(UserRole)` helper from `tests/Pest.php`.
- Run Pint before finalizing any PHP change: `vendor/bin/pint --dirty --format agent`.
- Frontend changes require `npm run build` to verify compilation.

---

## File Structure

**Backend**
- Create `database/migrations/2026_06_28_000001_add_completed_at_to_courses_users_table.php` — pivot completion timestamp.
- Create `database/migrations/2026_06_28_000002_add_unique_index_to_user_progress_table.php` — dedupe guard.
- Modify `app/Models/Page.php` — cast `status` to `CourseStatus`, add `published()` scope.
- Modify `app/Models/Course.php` — add `completed_at` to pivot.
- Modify `app/Policies/CoursePolicy.php` — add `take()` and `viewCertificate()`.
- Create `app/Actions/Courses/LoadCoursePlayer.php` — build player payload.
- Create `app/Actions/Courses/CompletePage.php` — record completion + gating + completion stamp.
- Create `app/Http/Controllers/CourseLearnController.php` — player + complete endpoints.
- Create `app/Http/Controllers/CourseCertificateController.php` — certificate page.
- Modify `app/Actions/Courses/ListCourses.php` — attach `can_take` + `progress_percent` for the index entry point.
- Modify `routes/web.php` — learn + certificate routes.

**Frontend**
- Create `resources/js/Pages/Courses/Learn.vue` — the player.
- Create `resources/js/Pages/Courses/Certificate.vue` — printable certificate.
- Modify `resources/js/Pages/Courses/Index.vue` — "Take / Continue" entry point + progress.

**Tests**
- Create `tests/Feature/CoursePolicyTakeTest.php`
- Create `tests/Feature/LoadCoursePlayerTest.php`
- Create `tests/Feature/CompletePageTest.php`
- Create `tests/Feature/CourseLearnControllerTest.php`
- Create `tests/Feature/CourseCertificateControllerTest.php`
- Modify/extend course index coverage in `tests/Feature/CourseLearnControllerTest.php` (ListCourses entry-point assertions live with the learn tests).

---

### Task 1: Data model — migrations, Page cast/scope, Course pivot

**Files:**
- Create: `database/migrations/2026_06_28_000001_add_completed_at_to_courses_users_table.php`
- Create: `database/migrations/2026_06_28_000002_add_unique_index_to_user_progress_table.php`
- Modify: `app/Models/Page.php`
- Modify: `app/Models/Course.php`
- Test: `tests/Feature/LoadCoursePlayerTest.php` (scope test added here; reused later)

**Interfaces:**
- Produces:
  - `Page::scopePublished(Builder $query): Builder` — `where('status', CourseStatus::Published)`.
  - `Page` casts `status` => `CourseStatus`.
  - `courses_users.completed_at` nullable timestamp; readable via `$course->students()->...->first()->pivot->completed_at`.
  - `user_progress` unique on `(user_id, page_id)`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/LoadCoursePlayerTest.php`:

```php
<?php

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Page;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('published scope returns only published pages', function () {
    $course = Course::factory()->create();
    Page::factory()->forCourse($course, 1)->create(['status' => CourseStatus::Published]);
    Page::factory()->forCourse($course, 2)->create(['status' => CourseStatus::Draft]);
    Page::factory()->forCourse($course, 3)->create(['status' => CourseStatus::Archived]);

    $published = $course->pages()->published()->get();

    expect($published)->toHaveCount(1)
        ->and($published->first()->status)->toBe(CourseStatus::Published);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter='published scope returns only published pages'`
Expected: FAIL — `Call to undefined method ...published()` (and/or status is a string, not `CourseStatus`).

- [ ] **Step 3: Add the cast and scope to `app/Models/Page.php`**

Add the import and update the casts; add the scope method. The `$casts` array currently holds only `'order' => 'integer'`:

```php
use App\Enums\CourseStatus;
use Illuminate\Database\Eloquent\Builder;
```

```php
    protected $casts = [
        'order' => 'integer',
        'status' => CourseStatus::class,
    ];
```

```php
    /**
     * Scope a query to only published pages.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CourseStatus::Published);
    }
```

- [ ] **Step 4: Create the pivot migration**

`database/migrations/2026_06_28_000001_add_completed_at_to_courses_users_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses_users', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('is_instructor');
        });
    }

    public function down(): void
    {
        Schema::table('courses_users', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};
```

- [ ] **Step 5: Create the unique-index migration**

`database/migrations/2026_06_28_000002_add_unique_index_to_user_progress_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_progress', function (Blueprint $table) {
            $table->unique(['user_id', 'page_id']);
        });
    }

    public function down(): void
    {
        Schema::table('user_progress', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'page_id']);
        });
    }
};
```

- [ ] **Step 6: Add `completed_at` to the Course pivot**

In `app/Models/Course.php`, update the `users()` relation pivot columns:

```php
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'courses_users')
            ->withPivot('is_instructor', 'completed_at')
            ->withTimestamps();
    }
```

- [ ] **Step 7: Run the test to verify it passes**

Run: `php artisan test --compact --filter='published scope returns only published pages'`
Expected: PASS.

- [ ] **Step 8: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/Page.php app/Models/Course.php database/migrations/2026_06_28_000001_add_completed_at_to_courses_users_table.php database/migrations/2026_06_28_000002_add_unique_index_to_user_progress_table.php tests/Feature/LoadCoursePlayerTest.php
git commit -m "feat: page published scope, status cast, pivot completed_at, progress unique index"
```

---

### Task 2: CoursePolicy `take` + `viewCertificate`

**Files:**
- Modify: `app/Policies/CoursePolicy.php`
- Test: `tests/Feature/CoursePolicyTakeTest.php`

**Interfaces:**
- Consumes: `Course` pivot `completed_at` (Task 1).
- Produces:
  - `CoursePolicy::take(User $user, Course $course): bool` — enrolled student AND course Published.
  - `CoursePolicy::viewCertificate(User $user, Course $course): bool` — student's pivot `completed_at` is set.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/CoursePolicyTakeTest.php`:

```php
<?php

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Course;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('allows an enrolled student to take a published course', function () {
    $course = Course::factory()->published()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    expect($student->can('take', $course))->toBeTrue();
});

it('forbids taking a non-published course even when enrolled', function () {
    $course = Course::factory()->create(['status' => CourseStatus::Draft]);
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    expect($student->can('take', $course))->toBeFalse();
});

it('forbids a non-enrolled user from taking a course', function () {
    $course = Course::factory()->published()->create();
    $student = userWithRole(UserRole::Student);

    expect($student->can('take', $course))->toBeFalse();
});

it('forbids an instructor of the course from taking it', function () {
    $course = Course::factory()->published()->create();
    $instructor = userWithRole(UserRole::Instructor);
    $course->instructors()->attach($instructor, ['is_instructor' => true]);

    expect($instructor->can('take', $course))->toBeFalse();
});

it('only allows viewing a certificate once completed_at is set', function () {
    $course = Course::factory()->published()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    expect($student->can('viewCertificate', $course))->toBeFalse();

    $course->students()->updateExistingPivot($student->id, ['completed_at' => now()]);

    expect($student->fresh()->can('viewCertificate', $course->fresh()))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/CoursePolicyTakeTest.php`
Expected: FAIL — `take`/`viewCertificate` not defined (denied by default).

- [ ] **Step 3: Implement the policy methods**

In `app/Policies/CoursePolicy.php` add the import and methods:

```php
use App\Enums\CourseStatus;
```

```php
    /**
     * Determine whether the user may take (work through) the course.
     */
    public function take(User $user, Course $course): bool
    {
        return $course->status === CourseStatus::Published
            && $course->students()->whereKey($user->id)->exists();
    }

    /**
     * Determine whether the user may view their completion certificate.
     */
    public function viewCertificate(User $user, Course $course): bool
    {
        $student = $course->students()->whereKey($user->id)->first();

        return $student !== null && $student->pivot->completed_at !== null;
    }
```

Note: `Course::$status` is not cast, so `$course->status` is the raw string `'Published'`. Comparing to `CourseStatus::Published` (an enum) would be false. Add a status cast to keep the comparison enum-based and avoid a magic string. In `app/Models/Course.php` add:

```php
use App\Enums\CourseStatus;
```

```php
    protected $casts = [
        'status' => CourseStatus::class,
    ];
```

(The `Base` model defines its own `$casts` for timestamps; child `$casts` is merged by Eloquent, so declaring `status` here is safe.)

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/CoursePolicyTakeTest.php`
Expected: PASS (5 tests).

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Policies/CoursePolicy.php app/Models/Course.php tests/Feature/CoursePolicyTakeTest.php
git commit -m "feat: add take and viewCertificate course policies"
```

---

### Task 3: `LoadCoursePlayer` action

**Files:**
- Create: `app/Actions/Courses/LoadCoursePlayer.php`
- Test: `tests/Feature/LoadCoursePlayerTest.php` (append)

**Interfaces:**
- Consumes: `Page::published()` scope, `Course` pivot `completed_at` (Task 1).
- Produces: `LoadCoursePlayer::execute(Course $course, User $user, ?Page $currentPage = null): array` with shape:
  ```
  [
    'course' => ['id','title','code'],
    'pages' => [ ['id','title','order','is_completed','is_locked'], ... ],   // published only, by order
    'current_page' => ['id','title','content'] | null,
    'progress' => ['completed_count','total_count','percent'],
    'is_complete' => bool,
    'completed_at' => string|null,
  ]
  ```
  Locking rule: a page is unlocked iff every earlier published page is completed (the first incomplete page is unlocked; all pages after it are locked). `current_page` = requested page if provided and unlocked, else first incomplete, else last published page.

- [ ] **Step 1: Write the failing tests** (append to `tests/Feature/LoadCoursePlayerTest.php`)

```php
use App\Actions\Courses\LoadCoursePlayer;
use App\Models\User;
use App\Models\UserProgress;

it('builds player state with locked, completed flags and percent', function () {
    $course = Course::factory()->published()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    $page1 = Page::factory()->forCourse($course, 1)->create(['status' => CourseStatus::Published]);
    $page2 = Page::factory()->forCourse($course, 2)->create(['status' => CourseStatus::Published]);
    $page3 = Page::factory()->forCourse($course, 3)->create(['status' => CourseStatus::Published]);
    Page::factory()->forCourse($course, 4)->create(['status' => CourseStatus::Draft]);

    UserProgress::create(['user_id' => $student->id, 'course_id' => $course->id, 'page_id' => $page1->id]);

    $state = app(LoadCoursePlayer::class)->execute($course, $student);

    expect($state['pages'])->toHaveCount(3)
        ->and($state['progress'])->toMatchArray(['completed_count' => 1, 'total_count' => 3, 'percent' => 33])
        ->and($state['is_complete'])->toBeFalse()
        ->and($state['pages'][0])->toMatchArray(['id' => $page1->id, 'is_completed' => true, 'is_locked' => false])
        ->and($state['pages'][1])->toMatchArray(['id' => $page2->id, 'is_completed' => false, 'is_locked' => false])
        ->and($state['pages'][2])->toMatchArray(['id' => $page3->id, 'is_completed' => false, 'is_locked' => true])
        ->and($state['current_page']['id'])->toBe($page2->id);
});

it('reports completion when every published page is done', function () {
    $course = Course::factory()->published()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false, 'completed_at' => now()]);

    $page1 = Page::factory()->forCourse($course, 1)->create(['status' => CourseStatus::Published]);
    $page2 = Page::factory()->forCourse($course, 2)->create(['status' => CourseStatus::Published]);
    foreach ([$page1, $page2] as $page) {
        UserProgress::create(['user_id' => $student->id, 'course_id' => $course->id, 'page_id' => $page->id]);
    }

    $state = app(LoadCoursePlayer::class)->execute($course, $student);

    expect($state['is_complete'])->toBeTrue()
        ->and($state['progress']['percent'])->toBe(100)
        ->and($state['completed_at'])->not->toBeNull()
        ->and($state['current_page']['id'])->toBe($page2->id);
});

it('falls back to first incomplete page when a locked page is requested', function () {
    $course = Course::factory()->published()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    $page1 = Page::factory()->forCourse($course, 1)->create(['status' => CourseStatus::Published]);
    $page2 = Page::factory()->forCourse($course, 2)->create(['status' => CourseStatus::Published]);

    $state = app(LoadCoursePlayer::class)->execute($course, $student, $page2);

    expect($state['current_page']['id'])->toBe($page1->id);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/LoadCoursePlayerTest.php`
Expected: FAIL — `Class "App\Actions\Courses\LoadCoursePlayer" not found`.

- [ ] **Step 3: Implement the action**

Create `app/Actions/Courses/LoadCoursePlayer.php`:

```php
<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\Page;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Support\Collection;

class LoadCoursePlayer
{
    /**
     * Build the take-course player payload for a student.
     *
     * @return array<string, mixed>
     */
    public function execute(Course $course, User $user, ?Page $currentPage = null): array
    {
        $pages = $course->pages()->published()->get(['id', 'course_id', 'order', 'title']);

        $completed_page_ids = $this->completedPageIds($course, $user);

        $items = [];
        $is_locked = false;
        foreach ($pages as $page) {
            $is_completed = $completed_page_ids->contains($page->id);
            $items[] = [
                'id' => $page->id,
                'title' => $page->title,
                'order' => $page->order,
                'is_completed' => $is_completed,
                'is_locked' => $is_locked,
            ];

            if (! $is_completed) {
                $is_locked = true;
            }
        }

        $total_count = $pages->count();
        $completed_count = $pages->filter(fn (Page $page) => $completed_page_ids->contains($page->id))->count();
        $is_complete = $total_count > 0 && $completed_count === $total_count;

        $current = $this->resolveCurrentPage($pages, collect($items), $currentPage);

        $student = $course->students()->whereKey($user->id)->first();

        return [
            'course' => $course->only('id', 'title', 'code'),
            'pages' => $items,
            'current_page' => $current?->only('id', 'title', 'content'),
            'progress' => [
                'completed_count' => $completed_count,
                'total_count' => $total_count,
                'percent' => $total_count > 0 ? (int) round($completed_count / $total_count * 100) : 0,
            ],
            'is_complete' => $is_complete,
            'completed_at' => $student?->pivot->completed_at,
        ];
    }

    /**
     * The set of page ids the user has completed in this course.
     */
    private function completedPageIds(Course $course, User $user): Collection
    {
        return UserProgress::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->pluck('page_id');
    }

    /**
     * Resolve which page to display: the requested page if unlocked,
     * otherwise the first incomplete page, otherwise the last page.
     *
     * @param  Collection<int, Page>  $pages
     * @param  Collection<int, array<string, mixed>>  $items
     */
    private function resolveCurrentPage(Collection $pages, Collection $items, ?Page $requested): ?Page
    {
        if ($requested !== null) {
            $requested_item = $items->firstWhere('id', $requested->id);

            if ($requested_item !== null && ! $requested_item['is_locked']) {
                return Page::find($requested->id);
            }
        }

        $first_incomplete = $items->firstWhere('is_completed', false);
        $target_id = $first_incomplete['id'] ?? $pages->last()?->id;

        return $target_id !== null ? Page::find($target_id) : null;
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact tests/Feature/LoadCoursePlayerTest.php`
Expected: PASS (4 tests total in file).

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Actions/Courses/LoadCoursePlayer.php tests/Feature/LoadCoursePlayerTest.php
git commit -m "feat: add LoadCoursePlayer action"
```

---

### Task 4: `CompletePage` action

**Files:**
- Create: `app/Actions/Courses/CompletePage.php`
- Test: `tests/Feature/CompletePageTest.php`

**Interfaces:**
- Consumes: `Page::published()` scope, `Course` pivot `completed_at`, `user_progress` unique index (Task 1).
- Produces: `CompletePage::execute(User $user, Course $course, Page $page): ?int` — records completion idempotently after enforcing sequential gating, stamps pivot `completed_at` when the final published page is finished, and returns the next incomplete published page id (or `null` when the course is complete). Aborts 404 if the page is not a published page of the course; aborts 403 if an earlier published page is not yet complete.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/CompletePageTest.php`:

```php
<?php

use App\Actions\Courses\CompletePage;
use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Page;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function enrolledCourse(): array
{
    $course = Course::factory()->published()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    return [$course, $student];
}

it('records progress and returns the next incomplete page id', function () {
    [$course, $student] = enrolledCourse();
    $page1 = Page::factory()->forCourse($course, 1)->create(['status' => CourseStatus::Published]);
    $page2 = Page::factory()->forCourse($course, 2)->create(['status' => CourseStatus::Published]);

    $next = app(CompletePage::class)->execute($student, $course, $page1);

    expect($next)->toBe($page2->id)
        ->and(UserProgress::where('user_id', $student->id)->where('page_id', $page1->id)->exists())->toBeTrue();
});

it('records progress idempotently without duplicate rows', function () {
    [$course, $student] = enrolledCourse();
    $page1 = Page::factory()->forCourse($course, 1)->create(['status' => CourseStatus::Published]);

    $action = app(CompletePage::class);
    $action->execute($student, $course, $page1);
    $action->execute($student, $course, $page1);

    expect(UserProgress::where('user_id', $student->id)->where('page_id', $page1->id)->count())->toBe(1);
});

it('stamps completed_at when the final published page is completed', function () {
    [$course, $student] = enrolledCourse();
    $page1 = Page::factory()->forCourse($course, 1)->create(['status' => CourseStatus::Published]);
    $page2 = Page::factory()->forCourse($course, 2)->create(['status' => CourseStatus::Published]);

    $action = app(CompletePage::class);
    $action->execute($student, $course, $page1);
    expect($course->students()->whereKey($student->id)->first()->pivot->completed_at)->toBeNull();

    $next = $action->execute($student, $course, $page2);

    expect($next)->toBeNull()
        ->and($course->students()->whereKey($student->id)->first()->pivot->completed_at)->not->toBeNull();
});

it('aborts when an earlier published page is not yet complete', function () {
    [$course, $student] = enrolledCourse();
    Page::factory()->forCourse($course, 1)->create(['status' => CourseStatus::Published]);
    $page2 = Page::factory()->forCourse($course, 2)->create(['status' => CourseStatus::Published]);

    app(CompletePage::class)->execute($student, $course, $page2);
})->throws(Symfony\Component\HttpKernel\Exception\HttpException::class);

it('aborts when the page is not a published page of the course', function () {
    [$course, $student] = enrolledCourse();
    $draft = Page::factory()->forCourse($course, 1)->create(['status' => CourseStatus::Draft]);

    app(CompletePage::class)->execute($student, $course, $draft);
})->throws(Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/CompletePageTest.php`
Expected: FAIL — `Class "App\Actions\Courses\CompletePage" not found`.

- [ ] **Step 3: Implement the action**

Create `app/Actions/Courses/CompletePage.php`:

```php
<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\Page;
use App\Models\User;
use App\Models\UserProgress;

class CompletePage
{
    /**
     * Record the page as complete for the user, enforcing sequential gating,
     * and stamp course completion when every published page is finished.
     *
     * @return int|null  The next incomplete published page id, or null when complete.
     */
    public function execute(User $user, Course $course, Page $page): ?int
    {
        $pages = $course->pages()->published()->get(['id', 'order']);

        abort_unless($pages->contains('id', $page->id), 404);

        $completed_page_ids = UserProgress::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->pluck('page_id');

        foreach ($pages as $earlier) {
            if ($earlier->id === $page->id) {
                break;
            }

            abort_unless($completed_page_ids->contains($earlier->id), 403);
        }

        UserProgress::firstOrCreate([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'page_id' => $page->id,
        ]);

        $completed_page_ids = UserProgress::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->pluck('page_id');

        $this->stampCompletionIfFinished($course, $user, $pages, $completed_page_ids);

        $next = $pages->first(fn (Page $candidate) => ! $completed_page_ids->contains($candidate->id));

        return $next?->id;
    }

    /**
     * Stamp the pivot completed_at the first time all published pages are done.
     *
     * @param  \Illuminate\Support\Collection<int, Page>  $pages
     * @param  \Illuminate\Support\Collection<int, int>  $completed_page_ids
     */
    private function stampCompletionIfFinished(Course $course, User $user, $pages, $completed_page_ids): void
    {
        $all_complete = $pages->isNotEmpty()
            && $pages->every(fn (Page $page) => $completed_page_ids->contains($page->id));

        if (! $all_complete) {
            return;
        }

        $student = $course->students()->whereKey($user->id)->first();

        if ($student !== null && $student->pivot->completed_at === null) {
            $course->students()->updateExistingPivot($user->id, ['completed_at' => now()]);
        }
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact tests/Feature/CompletePageTest.php`
Expected: PASS (5 tests).

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Actions/Courses/CompletePage.php tests/Feature/CompletePageTest.php
git commit -m "feat: add CompletePage action with sequential gating"
```

---

### Task 5: Routes + `CourseLearnController`

**Files:**
- Create: `app/Http/Controllers/CourseLearnController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/CourseLearnControllerTest.php`

**Interfaces:**
- Consumes: `LoadCoursePlayer::execute` (Task 3), `CompletePage::execute` (Task 4), `CoursePolicy::take` (Task 2).
- Produces routes:
  - `courses.learn` → `GET /courses/{course}/learn`
  - `courses.learn.page` → `GET /courses/{course}/learn/{page}`
  - `courses.learn.complete` → `POST /courses/{course}/learn/{page}/complete`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/CourseLearnControllerTest.php`:

```php
<?php

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Page;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

function publishedCourseWithStudent(int $pageCount = 2): array
{
    $course = Course::factory()->published()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    $pages = collect(range(1, $pageCount))->map(
        fn (int $order) => Page::factory()->forCourse($course, $order)->create(['status' => CourseStatus::Published])
    );

    return [$course, $student, $pages];
}

it('renders the player for an enrolled student', function () {
    [$course, $student] = publishedCourseWithStudent();

    $this->actingAs($student)
        ->get(route('courses.learn', $course))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Learn')
            ->has('pages', 2)
            ->has('current_page')
            ->where('progress.total_count', 2)
        );
});

it('forbids a non-enrolled user from the player', function () {
    $course = Course::factory()->published()->create();
    $stranger = userWithRole(UserRole::Student);

    $this->actingAs($stranger)
        ->get(route('courses.learn', $course))
        ->assertForbidden();
});

it('forbids taking a non-published course', function () {
    $course = Course::factory()->create(['status' => CourseStatus::Draft]);
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    $this->actingAs($student)
        ->get(route('courses.learn', $course))
        ->assertForbidden();
});

it('completes a page and redirects to the next', function () {
    [$course, $student, $pages] = publishedCourseWithStudent();

    $this->actingAs($student)
        ->post(route('courses.learn.complete', [$course, $pages[0]]))
        ->assertRedirect(route('courses.learn.page', [$course, $pages[1]->id]));

    expect(UserProgress::where('user_id', $student->id)->where('page_id', $pages[0]->id)->exists())->toBeTrue();
});

it('redirects to the player with success after the final page', function () {
    [$course, $student, $pages] = publishedCourseWithStudent(1);

    $this->actingAs($student)
        ->post(route('courses.learn.complete', [$course, $pages[0]]))
        ->assertRedirect(route('courses.learn', $course))
        ->assertSessionHas('success');
});

it('forbids completing a locked page', function () {
    [$course, $student, $pages] = publishedCourseWithStudent();

    $this->actingAs($student)
        ->post(route('courses.learn.complete', [$course, $pages[1]]))
        ->assertForbidden();
});

it('attaches take and progress data to the course index for enrolled students', function () {
    [$course, $student, $pages] = publishedCourseWithStudent(2);
    UserProgress::create(['user_id' => $student->id, 'course_id' => $course->id, 'page_id' => $pages[0]->id]);

    $this->actingAs($student)
        ->get(route('courses.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->where('courses.data.0.can_take', true)
            ->where('courses.data.0.progress_percent', 50)
        );
});
```

(The final test exercises the Task 7 change; it is grouped here so the learn feature has one cohesive test file.)

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/CourseLearnControllerTest.php`
Expected: FAIL — route `courses.learn` not defined.

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/CourseLearnController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Actions\Courses\CompletePage;
use App\Actions\Courses\LoadCoursePlayer;
use App\Models\Course;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseLearnController extends Controller
{
    /**
     * Show the course player at the first incomplete page.
     */
    public function show(Request $request, Course $course, LoadCoursePlayer $loadCoursePlayer): Response
    {
        $this->authorize('take', $course);

        return Inertia::render('Courses/Learn', $loadCoursePlayer->execute($course, $request->user()));
    }

    /**
     * Show the course player at a specific (unlocked) page.
     */
    public function showPage(Request $request, Course $course, Page $page, LoadCoursePlayer $loadCoursePlayer): Response
    {
        $this->authorize('take', $course);

        return Inertia::render('Courses/Learn', $loadCoursePlayer->execute($course, $request->user(), $page));
    }

    /**
     * Mark the page complete and advance to the next incomplete page.
     */
    public function complete(Request $request, Course $course, Page $page, CompletePage $completePage): RedirectResponse
    {
        $this->authorize('take', $course);

        $next_page_id = $completePage->execute($request->user(), $course, $page);

        if ($next_page_id === null) {
            return redirect()
                ->route('courses.learn', $course)
                ->with('success', 'Course complete!');
        }

        return redirect()->route('courses.learn.page', [$course, $next_page_id]);
    }
}
```

- [ ] **Step 4: Register the routes**

In `routes/web.php`, add the import at the top:

```php
use App\Http\Controllers\CourseLearnController;
```

Inside the `Route::middleware('auth')->prefix('courses')->name('courses.')->group(...)` block, add these (place them before the `/{course}` show route is fine — distinct segment counts don't collide, but keep them grouped with the other course routes):

```php
    // Take a course (player)
    Route::get('/{course}/learn', [CourseLearnController::class, 'show'])->name('learn');
    Route::get('/{course}/learn/{page}', [CourseLearnController::class, 'showPage'])
        ->scopeBindings()
        ->name('learn.page');
    Route::post('/{course}/learn/{page}/complete', [CourseLearnController::class, 'complete'])
        ->scopeBindings()
        ->name('learn.complete');
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --compact tests/Feature/CourseLearnControllerTest.php`
Expected: the index-data test (last one) FAILS until Task 7; all six learn tests PASS. Run only the learn tests now:

Run: `php artisan test --compact tests/Feature/CourseLearnControllerTest.php --filter='player|next|final|locked|enrolled student|non-published'`
Expected: PASS for the six learn tests. (The index test is implemented in Task 7.)

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/CourseLearnController.php routes/web.php tests/Feature/CourseLearnControllerTest.php
git commit -m "feat: add course learn controller and routes"
```

---

### Task 6: `CourseCertificateController` + route

**Files:**
- Create: `app/Http/Controllers/CourseCertificateController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/CourseCertificateControllerTest.php`

**Interfaces:**
- Consumes: `CoursePolicy::viewCertificate` (Task 2), pivot `completed_at` (Task 1).
- Produces route `courses.certificate` → `GET /courses/{course}/certificate`, rendering `Courses/Certificate` with props `course` (`id,title,code`), `student` (`id,first_name,last_name`), `completed_at`.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/CourseCertificateControllerTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Models\Course;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

it('forbids viewing the certificate before completion', function () {
    $course = Course::factory()->published()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    $this->actingAs($student)
        ->get(route('courses.certificate', $course))
        ->assertForbidden();
});

it('renders the certificate after completion', function () {
    $course = Course::factory()->published()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false, 'completed_at' => now()]);

    $this->actingAs($student)
        ->get(route('courses.certificate', $course))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Certificate')
            ->where('course.id', $course->id)
            ->where('student.id', $student->id)
            ->has('completed_at')
        );
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/CourseCertificateControllerTest.php`
Expected: FAIL — route `courses.certificate` not defined.

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/CourseCertificateController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseCertificateController extends Controller
{
    /**
     * Show the completion certificate for a finished course.
     */
    public function show(Request $request, Course $course): Response
    {
        $this->authorize('viewCertificate', $course);

        $user = $request->user();
        $student = $course->students()->whereKey($user->id)->first();

        return Inertia::render('Courses/Certificate', [
            'course' => $course->only('id', 'title', 'code'),
            'student' => $user->only('id', 'first_name', 'last_name'),
            'completed_at' => $student->pivot->completed_at,
        ]);
    }
}
```

- [ ] **Step 4: Register the route**

In `routes/web.php` add the import:

```php
use App\Http\Controllers\CourseCertificateController;
```

Inside the courses group:

```php
    // Completion certificate
    Route::get('/{course}/certificate', [CourseCertificateController::class, 'show'])->name('certificate');
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --compact tests/Feature/CourseCertificateControllerTest.php`
Expected: PASS (2 tests).

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/CourseCertificateController.php routes/web.php tests/Feature/CourseCertificateControllerTest.php
git commit -m "feat: add course certificate controller and route"
```

---

### Task 7: Index entry-point data in `ListCourses`

**Files:**
- Modify: `app/Actions/Courses/ListCourses.php`
- Test: `tests/Feature/CourseLearnControllerTest.php` (the index-data test from Task 5)

**Interfaces:**
- Consumes: `user_progress`, `pages.status`, `courses_users` tables.
- Produces: each paginated course gains `can_take` (bool: enrolled student AND Published) and `progress_percent` (int 0–100) attributes.

- [ ] **Step 1: Confirm the failing test**

Run: `php artisan test --compact tests/Feature/CourseLearnControllerTest.php --filter='attaches take and progress'`
Expected: FAIL — `courses.data.0.can_take` missing.

- [ ] **Step 2: Implement the entry-point data**

In `app/Actions/Courses/ListCourses.php`, add the import:

```php
use App\Enums\CourseStatus;
```

Replace the body after `$taught_course_ids` is computed (keep the existing `$query`, `$is_admin`, and `$taught_course_ids` logic) with the following, which also computes student enrollment and progress:

```php
        $enrolled_student_course_ids = DB::table('courses_users')
            ->where('user_id', $user->id)
            ->where('is_instructor', false)
            ->whereNull('deleted_at')
            ->pluck('course_id');

        $published_page_counts = DB::table('pages')
            ->whereIn('course_id', $enrolled_student_course_ids)
            ->where('status', CourseStatus::Published->value)
            ->whereNull('deleted_at')
            ->groupBy('course_id')
            ->selectRaw('course_id, count(*) as total')
            ->pluck('total', 'course_id');

        $completed_page_counts = DB::table('user_progress')
            ->where('user_id', $user->id)
            ->whereIn('course_id', $enrolled_student_course_ids)
            ->whereNull('deleted_at')
            ->groupBy('course_id')
            ->selectRaw('course_id, count(*) as completed')
            ->pluck('completed', 'course_id');

        return $query->paginate($request->input('perPage', 15))
            ->withQueryString()
            ->through(function (Course $course) use (
                $is_admin,
                $taught_course_ids,
                $enrolled_student_course_ids,
                $published_page_counts,
                $completed_page_counts,
            ) {
                $course->can_update = $is_admin || $taught_course_ids->contains($course->id);

                $is_enrolled_student = $enrolled_student_course_ids->contains($course->id);
                $course->can_take = $is_enrolled_student && $course->status === CourseStatus::Published->value;

                $total = (int) ($published_page_counts[$course->id] ?? 0);
                $completed = (int) ($completed_page_counts[$course->id] ?? 0);
                $course->progress_percent = $total > 0 ? min(100, (int) round($completed / $total * 100)) : 0;

                return $course;
            });
```

Note: `$course->status` here is the raw selected string (the `select` in this action limits columns and the Course cast still applies on the model instance, but the DB comparison uses `CourseStatus::Published->value`). Comparing `$course->status === CourseStatus::Published->value` works because the model casts `status` to the enum — adjust to `$course->status === CourseStatus::Published` since the cast is active. Use the enum form:

```php
                $course->can_take = $is_enrolled_student && $course->status === CourseStatus::Published;
```

- [ ] **Step 3: Run the test to verify it passes**

Run: `php artisan test --compact tests/Feature/CourseLearnControllerTest.php --filter='attaches take and progress'`
Expected: PASS.

- [ ] **Step 4: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Actions/Courses/ListCourses.php
git commit -m "feat: expose can_take and progress_percent on course index"
```

---

### Task 8: Player page `Courses/Learn.vue`

**Files:**
- Create: `resources/js/Pages/Courses/Learn.vue`

**Interfaces:**
- Consumes props from `LoadCoursePlayer` payload (Task 3): `course`, `pages[]`, `current_page`, `progress`, `is_complete`, `completed_at`.
- Posts to `courses.learn.complete`; navigates via `courses.learn.page`; links certificate via `courses.certificate`.

- [ ] **Step 1: Create the component**

Create `resources/js/Pages/Courses/Learn.vue`:

```vue
<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Check, Lock, Award, CheckCircle2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    course: { type: Object, required: true },
    pages: { type: Array, required: true },
    current_page: { type: Object, default: null },
    progress: { type: Object, required: true },
    is_complete: { type: Boolean, default: false },
    completed_at: { type: [String, null], default: null },
});

const currentPageId = computed(() => props.current_page?.id ?? null);

const goToPage = (page) => {
    if (page.is_locked || page.id === currentPageId.value) {
        return;
    }
    router.visit(route('courses.learn.page', [props.course.id, page.id]));
};

const completeAndContinue = () => {
    if (!props.current_page) {
        return;
    }
    router.post(route('courses.learn.complete', [props.course.id, props.current_page.id]));
};

const currentIsCompleted = computed(() =>
    props.pages.find((page) => page.id === currentPageId.value)?.is_completed ?? false,
);
</script>

<template>
  <AppLayout>
    <Head :title="`Learn: ${course.title}`" />

    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">
      <div class="mb-6">
        <Link :href="route('courses.index')">
          <Button variant="outline">
            <ArrowLeft class="w-4 h-4" />
            Back to Courses
          </Button>
        </Link>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-[20rem_1fr] gap-6">
        <!-- Sidebar -->
        <Card class="shadow-md h-fit">
          <CardContent class="pt-6">
            <h2 class="text-lg font-bold text-darker-900">{{ course.title }}</h2>
            <p class="font-mono text-sm text-darker-500 mb-4">{{ course.code }}</p>

            <div class="mb-2 flex items-center justify-between text-sm text-darker-600">
              <span>{{ progress.completed_count }} of {{ progress.total_count }} complete</span>
              <span class="font-semibold">{{ progress.percent }}%</span>
            </div>
            <div class="h-2 w-full rounded-full bg-darker-200 mb-6">
              <div class="h-2 rounded-full bg-primary-600 transition-all" :style="{ width: `${progress.percent}%` }" />
            </div>

            <ul class="space-y-1">
              <li v-for="page in pages" :key="page.id">
                <button
                    type="button"
                    :disabled="page.is_locked"
                    class="w-full flex items-center gap-2 rounded-md px-3 py-2 text-left text-sm transition-colors"
                    :class="[
                      page.id === currentPageId ? 'bg-primary-100 text-primary-800 font-semibold' : 'hover:bg-darker-100 text-darker-700',
                      page.is_locked ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
                    ]"
                    @click="goToPage(page)"
                >
                  <Check v-if="page.is_completed" class="w-4 h-4 shrink-0 text-primary-600" />
                  <Lock v-else-if="page.is_locked" class="w-4 h-4 shrink-0 text-darker-400" />
                  <span v-else class="w-4 h-4 shrink-0 rounded-full border border-darker-300" />
                  <span class="truncate">{{ page.title }}</span>
                </button>
              </li>
            </ul>
          </CardContent>
        </Card>

        <!-- Main panel -->
        <div>
          <Card v-if="is_complete" class="shadow-lg">
            <CardContent class="pt-6 text-center py-16">
              <CheckCircle2 class="w-16 h-16 text-primary-600 mx-auto mb-4" />
              <h1 class="text-3xl font-bold text-darker-900 mb-2">Course complete!</h1>
              <p class="text-darker-600 mb-8">You have completed every page of this course.</p>
              <Link :href="route('courses.certificate', course.id)">
                <Button class="px-6">
                  <Award class="w-4 h-4" />
                  View certificate
                </Button>
              </Link>
            </CardContent>
          </Card>

          <Card v-else-if="current_page" class="shadow-lg">
            <CardContent class="pt-6">
              <h1 class="text-3xl font-bold text-darker-900 mb-6">{{ current_page.title }}</h1>
              <div class="prose max-w-none" v-html="current_page.content"></div>

              <div class="mt-8 pt-6 border-t border-darker-200 flex justify-end">
                <Button class="px-6" @click="completeAndContinue">
                  <Check class="w-4 h-4" />
                  {{ currentIsCompleted ? 'Continue' : 'Mark complete & continue' }}
                </Button>
              </div>
            </CardContent>
          </Card>

          <Card v-else class="shadow-lg">
            <CardContent class="pt-6 text-center py-16">
              <p class="text-darker-500 text-lg">This course has no published pages yet.</p>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
```

- [ ] **Step 2: Build to verify it compiles**

Run: `npm run build`
Expected: build succeeds; `Learn` chunk emitted, no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Courses/Learn.vue
git commit -m "feat: add course player Learn page"
```

---

### Task 9: Certificate page `Courses/Certificate.vue`

**Files:**
- Create: `resources/js/Pages/Courses/Certificate.vue`

**Interfaces:**
- Consumes props from `CourseCertificateController` (Task 6): `course` (`id,title,code`), `student` (`first_name,last_name`), `completed_at`.

- [ ] **Step 1: Create the component**

Create `resources/js/Pages/Courses/Certificate.vue`:

```vue
<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Printer, Award } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    course: { type: Object, required: true },
    student: { type: Object, required: true },
    completed_at: { type: [String, null], default: null },
});

const studentName = computed(() => `${props.student.first_name ?? ''} ${props.student.last_name ?? ''}`.trim());

const printCertificate = () => {
    window.print();
};
</script>

<template>
  <AppLayout>
    <Head :title="`Certificate: ${course.title}`" />

    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">
      <div class="mb-6 flex items-center justify-between print:hidden">
        <Link :href="route('courses.learn', course.id)">
          <Button variant="outline">
            <ArrowLeft class="w-4 h-4" />
            Back to Course
          </Button>
        </Link>
        <Button @click="printCertificate">
          <Printer class="w-4 h-4" />
          Print
        </Button>
      </div>

      <div class="mx-auto max-w-3xl bg-white border-8 border-primary-600 rounded-lg p-12 text-center shadow-lg">
        <Award class="w-16 h-16 text-primary-600 mx-auto mb-6" />
        <p class="text-sm uppercase tracking-widest text-darker-500 mb-2">Certificate of Completion</p>
        <p class="text-darker-600 mb-6">This certifies that</p>
        <h1 class="text-4xl font-bold text-darker-900 mb-6">{{ studentName }}</h1>
        <p class="text-darker-600 mb-2">has successfully completed</p>
        <h2 class="text-2xl font-semibold text-darker-900">{{ course.title }}</h2>
        <p class="font-mono text-darker-500 mb-8">{{ course.code }}</p>
        <p v-if="completed_at" class="text-darker-600">Completed on {{ completed_at }}</p>
      </div>
    </div>
  </AppLayout>
</template>
```

- [ ] **Step 2: Build to verify it compiles**

Run: `npm run build`
Expected: build succeeds; `Certificate` chunk emitted, no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Courses/Certificate.vue
git commit -m "feat: add course completion certificate page"
```

---

### Task 10: Index entry point in `Courses/Index.vue`

**Files:**
- Modify: `resources/js/Pages/Courses/Index.vue`

**Interfaces:**
- Consumes `course.can_take` and `course.progress_percent` (Task 7).
- Links to `courses.learn`.

- [ ] **Step 1: Add the "Take / Continue" action**

In `resources/js/Pages/Courses/Index.vue`, add `GraduationCap` to the lucide import:

```js
import { Plus, Search, Inbox, Eye, Pencil, Trash2, GraduationCap } from 'lucide-vue-next';
```

In the actions cell (the `<td>` containing the View/Edit/Delete buttons), add the take button as the first action, before the View button:

```vue
                      <Button
                          v-if="course.can_take"
                          variant="default"
                          size="icon-sm"
                          :aria-label="course.progress_percent > 0 ? 'Continue course' : 'Take course'"
                          :title="course.progress_percent > 0 ? `Continue (${course.progress_percent}%)` : 'Take course'"
                          @click="router.visit(route('courses.learn', course.id))"
                      >
                        <GraduationCap class="w-4 h-4" />
                      </Button>
```

- [ ] **Step 2: Build to verify it compiles**

Run: `npm run build`
Expected: build succeeds, no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Courses/Index.vue
git commit -m "feat: add take/continue course entry point to index"
```

---

### Task 11: Full suite + Pint gate

**Files:** none (verification).

- [ ] **Step 1: Run the full backend test suite**

Run: `php artisan test --compact`
Expected: all tests pass, including the new learn/certificate/policy/action tests and existing course tests.

- [ ] **Step 2: Pint check**

Run: `vendor/bin/pint --dirty --format agent`
Expected: no style violations introduced (already run per task; final sweep).

- [ ] **Step 3: Final build**

Run: `npm run build`
Expected: build succeeds.

- [ ] **Step 4: Commit any residual formatting**

```bash
git add -A
git commit -m "chore: final lint and build for take-course feature" || echo "nothing to commit"
```

---

## Self-Review

**Spec coverage:**
- Page status cast + published scope → Task 1. ✓
- `user_progress` uniqueness + idempotent writes → Task 1 (index) + Task 4 (`firstOrCreate`). ✓
- Pivot `completed_at` → Task 1 (migration/relation) + Task 4 (stamp). ✓
- `LoadCoursePlayer` (locked/completed/percent/current-page) → Task 3. ✓
- `CompletePage` (enrollment via policy, page-published/belongs, gating, completion stamp, next id) → Task 4 + Task 5 (policy authorize for enrollment/published). ✓
- `take` / `viewCertificate` policies → Task 2. ✓
- Routes + controllers (learn show/showPage/complete, certificate) → Tasks 5–6. ✓
- Player page, certificate page, index entry point → Tasks 8–10. ✓
- Tests for gating/idempotency/forbidden/excluded-draft/percent/certificate → Tasks 2–7. ✓
- Edge case: zero published pages → Task 8 empty state; `is_complete=false` derives from Task 3. ✓
- Edge case: completion retained after later page published → `completed_at` stamped once (Task 4), `viewCertificate` still true (Task 2). ✓

**Placeholder scan:** No TBD/TODO; all steps contain concrete code and commands.

**Type consistency:** `execute(Course, User, ?Page)` (LoadCoursePlayer) and `execute(User, Course, Page): ?int` (CompletePage) match controller calls in Task 5. Route names `courses.learn`, `courses.learn.page`, `courses.learn.complete`, `courses.certificate` are used consistently across controller, tests, and Vue. Props (`can_take`, `progress_percent`, `progress.percent`, `current_page`, `is_complete`, `completed_at`) match between actions, controllers, and components.

**Note on `CompletePage` auth context:** the `CompletePage` action tests call `app(CompletePage::class)->execute(...)` directly without `actingAs`. This is intentional — the action does not depend on the authenticated user (it takes `$user` explicitly), and `user_progress.created_by_id` has a DB default, so audit stamping is not required for these unit-style tests. Authentication/authorization is covered separately through the controller tests in Task 5.
