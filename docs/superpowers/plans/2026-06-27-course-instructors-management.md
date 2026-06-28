# Course Instructor Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let admins and a course's own instructors assign and remove instructors on a course, guaranteeing every course always has at least one instructor (the creator is auto-assigned).

**Architecture:** Reuse the existing `courses_users` pivot (`is_instructor` flag) — no schema change. Two thin endpoints on a new `CourseInstructorController` delegate to single-purpose Action classes. The `≥1 instructor` invariant lives in the Action layer; eligibility/duplicate checks live in a FormRequest; authorization reuses an `update`-mirroring policy ability. The existing `Courses/Show.vue` Instructors panel gains inline add/remove controls gated on a `can.manage_instructors` flag.

**Tech Stack:** Laravel 13 (PHP 8.4), Inertia v3 + Vue 3, Spatie Permission (roles), Pest 4 (feature tests), Tailwind v4.

## Global Constraints

- Naming (project + global standards): PHP variables `snake_case`; methods/functions `camelCase`; classes `TitleCase`. Use full descriptive variable names (`$assignable_instructors`, not `$users`).
- Controllers stay thin: resolve the Action via method injection, pass validated data, return the response. Business logic lives in single-purpose Actions in `app/Actions/Courses/`, each with one public `execute()` and explicit type declarations.
- Use constructor property promotion; explicit return types and param type hints on every method; PHPDoc blocks over inline comments; array-shape PHPDoc where relevant.
- All tests are Pest (`it()`/`expect()`), feature tests under `tests/Feature/`. Use factories and the `userWithRole(string $role)` helper from `tests/Pest.php`.
- "Instructor" / "Admin" eligibility is by **Spatie role** (`hasAnyRole`/`User::role(...)`), NOT the `users.role` column. The factory does not assign a Spatie role — use `userWithRole('Instructor'|'Admin'|'Student')` in tests.
- After modifying any PHP file, run `vendor/bin/pint --dirty --format agent` before committing.
- Run the new test file after each task: `php artisan test --compact tests/Feature/CourseInstructorControllerTest.php`.

## File Structure

**Create:**
- `app/Actions/Courses/AssignInstructor.php` — attach a user to a course as instructor.
- `app/Actions/Courses/RemoveInstructor.php` — detach an instructor, enforcing the ≥1 invariant.
- `app/Actions/Courses/ListAssignableInstructors.php` — eligible users not yet assigned, for the add control.
- `app/Http/Requests/StoreCourseInstructorRequest.php` — validate `user_id` (exists, eligible role, not already assigned).
- `app/Http/Controllers/CourseInstructorController.php` — `store` + `destroy`.
- `tests/Feature/CourseInstructorControllerTest.php` — feature tests.

**Modify:**
- `app/Actions/Courses/CreateCourse.php` — accept the creator and auto-assign as instructor.
- `app/Http/Controllers/CourseController.php` — pass creator into `CreateCourse`; add `can.manage_instructors` + `assignable_instructors` to the Show response.
- `app/Policies/CoursePolicy.php` — add `manageInstructors` ability.
- `routes/web.php` — add the two nested instructor routes.
- `resources/js/Pages/Courses/Show.vue` — inline add/remove instructor UI.

---

### Task 1: Auto-assign the creator as instructor on course creation

**Files:**
- Modify: `app/Actions/Courses/CreateCourse.php`
- Modify: `app/Http/Controllers/CourseController.php:55-64`
- Test: `tests/Feature/CourseInstructorControllerTest.php` (new)

**Interfaces:**
- Produces: `CreateCourse::execute(array $attributes, \App\Models\User $creator): \App\Models\Course` — creates the course and attaches `$creator` to `instructors()` with `is_instructor = true`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/CourseInstructorControllerTest.php`:

```php
<?php

use App\Enums\CourseStatus;
use App\Models\Course;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('assigns the creator as an instructor when a course is created', function () {
    $creator = userWithRole('Instructor');

    $this->actingAs($creator)->post(route('courses.store'), [
        'status' => CourseStatus::Draft->value,
        'title' => 'Creator Course',
        'code' => 'CREATE-101',
    ]);

    $course = Course::firstWhere('code', 'CREATE-101');

    expect($course)->not->toBeNull()
        ->and($course->instructors()->whereKey($creator->id)->exists())->toBeTrue()
        ->and($course->instructors()->count())->toBe(1);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="assigns the creator as an instructor"`
Expected: FAIL — no instructor is attached (count is 0), or a `TypeError`/arg error once the action signature changes.

- [ ] **Step 3: Update `CreateCourse` to attach the creator**

Replace the body of `app/Actions/Courses/CreateCourse.php`:

```php
<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\User;
use App\Traits\SanitizesHtml;

class CreateCourse
{
    use SanitizesHtml;

    /**
     * Create a new course and assign its creator as an instructor.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, User $creator): Course
    {
        if (array_key_exists('description', $attributes)) {
            $attributes['description'] = $this->sanitizeHtml($attributes['description']);
        }

        $course = Course::create($attributes);

        $course->instructors()->attach($creator, ['is_instructor' => true]);

        return $course;
    }
}
```

- [ ] **Step 4: Pass the creator from the controller**

In `app/Http/Controllers/CourseController.php`, update the `store` method call (line ~59):

```php
$course = $createCourse->execute($request->validated(), $request->user());
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --compact tests/Feature/CourseInstructorControllerTest.php`
Expected: PASS (1 test).

Also confirm no regression in the existing course tests:
Run: `php artisan test --compact tests/Feature/CourseControllerTest.php`
Expected: PASS (all).

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Actions/Courses/CreateCourse.php app/Http/Controllers/CourseController.php tests/Feature/CourseInstructorControllerTest.php
git commit -m "feat: auto-assign course creator as instructor"
```

---

### Task 2: Add the `manageInstructors` policy ability

**Files:**
- Modify: `app/Policies/CoursePolicy.php`
- Test: `tests/Feature/CourseInstructorControllerTest.php`

**Interfaces:**
- Produces: `CoursePolicy::manageInstructors(User $user, Course $course): bool` — true for admins or an instructor already assigned to the course (mirrors `update`).

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/CourseInstructorControllerTest.php`:

```php
it('authorizes admins and assigned instructors to manage instructors', function () {
    $course = Course::factory()->create();
    $instructor = userWithRole('Instructor');
    $course->instructors()->attach($instructor, ['is_instructor' => true]);

    $admin = userWithRole('Admin');
    $other_instructor = userWithRole('Instructor');
    $student = userWithRole('Student');

    expect($admin->can('manageInstructors', $course))->toBeTrue()
        ->and($instructor->can('manageInstructors', $course))->toBeTrue()
        ->and($other_instructor->can('manageInstructors', $course))->toBeFalse()
        ->and($student->can('manageInstructors', $course))->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="authorizes admins and assigned instructors"`
Expected: FAIL — `manageInstructors` ability is not defined (all `can()` calls return false, so the admin/instructor assertions fail).

- [ ] **Step 3: Add the ability**

In `app/Policies/CoursePolicy.php`, add this method after `update()` (before `delete()`):

```php
    /**
     * Managing instructors follows the same rule as updating.
     */
    public function manageInstructors(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter="authorizes admins and assigned instructors"`
Expected: PASS.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Policies/CoursePolicy.php tests/Feature/CourseInstructorControllerTest.php
git commit -m "feat: add manageInstructors course policy ability"
```

---

### Task 3: AssignInstructor + RemoveInstructor actions

**Files:**
- Create: `app/Actions/Courses/AssignInstructor.php`
- Create: `app/Actions/Courses/RemoveInstructor.php`
- Test: `tests/Feature/CourseInstructorControllerTest.php`

**Interfaces:**
- Consumes: `Course::instructors()` (BelongsToMany scoped to `is_instructor = true`).
- Produces:
  - `AssignInstructor::execute(Course $course, User $user): void` — attaches `$user` with `is_instructor = true`.
  - `RemoveInstructor::execute(Course $course, User $user): void` — detaches `$user`; throws `Illuminate\Validation\ValidationException` (key `user`) when `$user` is the course's only instructor.

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/CourseInstructorControllerTest.php`:

```php
use App\Actions\Courses\AssignInstructor;
use App\Actions\Courses\RemoveInstructor;
use App\Models\User;
use Illuminate\Validation\ValidationException;
```

(Add those `use` statements to the existing import block at the top of the file, not inline.)

```php
it('attaches a user as an instructor via the AssignInstructor action', function () {
    $course = Course::factory()->create();
    $user = userWithRole('Instructor');

    app(AssignInstructor::class)->execute($course, $user);

    expect($course->instructors()->whereKey($user->id)->exists())->toBeTrue();
});

it('detaches a non-last instructor via the RemoveInstructor action', function () {
    $course = Course::factory()->create();
    $keep = userWithRole('Instructor');
    $remove = userWithRole('Instructor');
    $course->instructors()->attach($keep, ['is_instructor' => true]);
    $course->instructors()->attach($remove, ['is_instructor' => true]);

    app(RemoveInstructor::class)->execute($course, $remove);

    expect($course->instructors()->whereKey($remove->id)->exists())->toBeFalse()
        ->and($course->instructors()->count())->toBe(1);
});

it('refuses to remove the last instructor', function () {
    $course = Course::factory()->create();
    $only = userWithRole('Instructor');
    $course->instructors()->attach($only, ['is_instructor' => true]);

    expect(fn () => app(RemoveInstructor::class)->execute($course, $only))
        ->toThrow(ValidationException::class);
    expect($course->instructors()->count())->toBe(1);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter="AssignInstructor action|non-last instructor|last instructor"`
Expected: FAIL — the action classes do not exist (`Target class [AssignInstructor] does not exist` / resolution error).

- [ ] **Step 3: Create `AssignInstructor`**

Create `app/Actions/Courses/AssignInstructor.php`:

```php
<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\User;

class AssignInstructor
{
    /**
     * Assign a user to the course as an instructor.
     */
    public function execute(Course $course, User $user): void
    {
        $course->instructors()->attach($user, ['is_instructor' => true]);
    }
}
```

- [ ] **Step 4: Create `RemoveInstructor`**

Create `app/Actions/Courses/RemoveInstructor.php`:

```php
<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RemoveInstructor
{
    /**
     * Remove an instructor from the course, keeping at least one assigned.
     *
     * @throws ValidationException when the user is the course's only instructor
     */
    public function execute(Course $course, User $user): void
    {
        $is_only_instructor = $course->instructors()->whereKey($user->id)->exists()
            && $course->instructors()->count() === 1;

        if ($is_only_instructor) {
            throw ValidationException::withMessages([
                'user' => 'A course must have at least one instructor.',
            ]);
        }

        $course->instructors()->detach($user);
    }
}
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --compact --filter="AssignInstructor action|non-last instructor|last instructor"`
Expected: PASS (3 tests).

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Actions/Courses/AssignInstructor.php app/Actions/Courses/RemoveInstructor.php tests/Feature/CourseInstructorControllerTest.php
git commit -m "feat: add AssignInstructor and RemoveInstructor actions"
```

---

### Task 4: StoreCourseInstructorRequest validation

**Files:**
- Create: `app/Http/Requests/StoreCourseInstructorRequest.php`
- Test: covered indirectly in Task 5 (controller). This task only builds the request class; its rules are exercised by Task 5's validation tests.

**Interfaces:**
- Consumes: route-bound `{course}` via `$this->route('course')`.
- Produces: `StoreCourseInstructorRequest` with validated key `user_id` (int). Fails validation when the user does not exist, lacks the Instructor/Admin role, or is already a course instructor.

- [ ] **Step 1: Create the FormRequest**

Create `app/Http/Requests/StoreCourseInstructorRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCourseInstructorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<int, mixed>|string>
     */
    public function rules(): array
    {
        $course = $this->route('course');

        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                function (string $attribute, mixed $value, callable $fail) use ($course): void {
                    $user = User::find($value);

                    if ($user === null) {
                        return;
                    }

                    if (! $user->hasAnyRole(['Admin', 'Instructor'])) {
                        $fail('The selected user must be an instructor or admin.');
                    } elseif ($course->instructors()->whereKey($value)->exists()) {
                        $fail('This user is already an instructor of the course.');
                    }
                },
            ],
        ];
    }
}
```

- [ ] **Step 2: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Requests/StoreCourseInstructorRequest.php
git commit -m "feat: add StoreCourseInstructorRequest validation"
```

(Validation behavior is verified end-to-end in Task 5.)

---

### Task 5: CourseInstructorController + routes

**Files:**
- Create: `app/Http/Controllers/CourseInstructorController.php`
- Modify: `routes/web.php:17-44` (inside the existing `courses` route group)
- Test: `tests/Feature/CourseInstructorControllerTest.php`

**Interfaces:**
- Consumes: `AssignInstructor::execute`, `RemoveInstructor::execute`, `StoreCourseInstructorRequest`, `CoursePolicy::manageInstructors`.
- Produces routes:
  - `courses.instructors.store` → `POST /courses/{course}/instructors`
  - `courses.instructors.destroy` → `DELETE /courses/{course}/instructors/{user}`

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/CourseInstructorControllerTest.php`. (Add a small helper at the top, below the imports, to build a course that already has one instructor.)

```php
/**
 * Create a course that already has a single assigned instructor.
 *
 * @return array{0: \App\Models\Course, 1: \App\Models\User}
 */
function courseWithInstructor(): array
{
    $course = Course::factory()->create();
    $instructor = userWithRole('Instructor');
    $course->instructors()->attach($instructor, ['is_instructor' => true]);

    return [$course, $instructor];
}
```

```php
it('lets an admin add an instructor', function () {
    [$course] = courseWithInstructor();
    $new_instructor = userWithRole('Instructor');

    $response = $this->actingAs(userWithRole('Admin'))
        ->post(route('courses.instructors.store', $course), [
            'user_id' => $new_instructor->id,
        ]);

    $response->assertRedirect(route('courses.show', $course));
    $response->assertSessionHas('success');
    expect($course->instructors()->whereKey($new_instructor->id)->exists())->toBeTrue();
});

it('lets an assigned instructor add another instructor', function () {
    [$course, $instructor] = courseWithInstructor();
    $new_instructor = userWithRole('Instructor');

    $response = $this->actingAs($instructor)
        ->post(route('courses.instructors.store', $course), [
            'user_id' => $new_instructor->id,
        ]);

    $response->assertRedirect(route('courses.show', $course));
    expect($course->instructors()->count())->toBe(2);
});

it('forbids a non-manager from adding an instructor', function () {
    [$course] = courseWithInstructor();
    $eligible_target = userWithRole('Instructor');
    $outsider = userWithRole('Instructor');

    $response = $this->actingAs($outsider)
        ->post(route('courses.instructors.store', $course), [
            'user_id' => $eligible_target->id,
        ]);

    $response->assertForbidden();
    expect($course->instructors()->whereKey($eligible_target->id)->exists())->toBeFalse();
});

it('forbids a student from adding an instructor', function () {
    [$course] = courseWithInstructor();
    $eligible_target = userWithRole('Instructor');

    $response = $this->actingAs(userWithRole('Student'))
        ->post(route('courses.instructors.store', $course), [
            'user_id' => $eligible_target->id,
        ]);

    $response->assertForbidden();
});

it('rejects adding a user without an instructor or admin role', function () {
    [$course] = courseWithInstructor();
    $student = userWithRole('Student');

    $response = $this->actingAs(userWithRole('Admin'))
        ->post(route('courses.instructors.store', $course), [
            'user_id' => $student->id,
        ]);

    $response->assertSessionHasErrors('user_id');
    expect($course->instructors()->whereKey($student->id)->exists())->toBeFalse();
});

it('rejects adding an already-assigned instructor', function () {
    [$course, $instructor] = courseWithInstructor();

    $response = $this->actingAs(userWithRole('Admin'))
        ->post(route('courses.instructors.store', $course), [
            'user_id' => $instructor->id,
        ]);

    $response->assertSessionHasErrors('user_id');
    expect($course->instructors()->count())->toBe(1);
});

it('lets an admin remove a non-last instructor', function () {
    [$course, $instructor] = courseWithInstructor();
    $second = userWithRole('Instructor');
    $course->instructors()->attach($second, ['is_instructor' => true]);

    $response = $this->actingAs(userWithRole('Admin'))
        ->delete(route('courses.instructors.destroy', ['course' => $course, 'user' => $second]));

    $response->assertRedirect(route('courses.show', $course));
    expect($course->instructors()->whereKey($second->id)->exists())->toBeFalse();
});

it('blocks removing the last instructor through the endpoint', function () {
    [$course, $instructor] = courseWithInstructor();

    $response = $this->actingAs(userWithRole('Admin'))
        ->delete(route('courses.instructors.destroy', ['course' => $course, 'user' => $instructor]));

    $response->assertSessionHasErrors('user');
    expect($course->instructors()->count())->toBe(1);
});

it('forbids a non-manager from removing an instructor', function () {
    [$course, $instructor] = courseWithInstructor();
    $second = userWithRole('Instructor');
    $course->instructors()->attach($second, ['is_instructor' => true]);

    $response = $this->actingAs(userWithRole('Instructor'))
        ->delete(route('courses.instructors.destroy', ['course' => $course, 'user' => $second]));

    $response->assertForbidden();
    expect($course->instructors()->count())->toBe(2);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter="add an instructor|remove a non-last|removing the last|already-assigned|without an instructor or admin role"`
Expected: FAIL — route `courses.instructors.store` is not defined (`RouteNotFoundException`).

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/CourseInstructorController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Actions\Courses\AssignInstructor;
use App\Actions\Courses\RemoveInstructor;
use App\Http\Requests\StoreCourseInstructorRequest;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class CourseInstructorController extends Controller
{
    /**
     * Assign an instructor to the course.
     */
    public function store(StoreCourseInstructorRequest $request, Course $course, AssignInstructor $assignInstructor): RedirectResponse
    {
        $this->authorize('manageInstructors', $course);

        $user = User::findOrFail($request->validated()['user_id']);

        $assignInstructor->execute($course, $user);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Instructor added successfully.');
    }

    /**
     * Remove an instructor from the course.
     */
    public function destroy(Course $course, User $user, RemoveInstructor $removeInstructor): RedirectResponse
    {
        $this->authorize('manageInstructors', $course);

        $removeInstructor->execute($course, $user);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Instructor removed successfully.');
    }
}
```

- [ ] **Step 4: Add the routes**

In `routes/web.php`, add the import near the top (after the existing `CourseController` import):

```php
use App\Http\Controllers\CourseInstructorController;
```

Then, inside the `courses` route group (the `Route::middleware('auth')->prefix('courses')->name('courses.')->group(...)` block), add before the closing `});`:

```php
    // Assign an instructor to a course
    Route::post('/{course}/instructors', [CourseInstructorController::class, 'store'])->name('instructors.store');

    // Remove an instructor from a course
    Route::delete('/{course}/instructors/{user}', [CourseInstructorController::class, 'destroy'])->name('instructors.destroy');
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --compact tests/Feature/CourseInstructorControllerTest.php`
Expected: PASS (all tests in the file).

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/CourseInstructorController.php routes/web.php tests/Feature/CourseInstructorControllerTest.php
git commit -m "feat: add course instructor assign/remove endpoints"
```

---

### Task 6: Surface manage flag + assignable instructors on the Show page

**Files:**
- Create: `app/Actions/Courses/ListAssignableInstructors.php`
- Modify: `app/Http/Controllers/CourseController.php:69-77` (the `show` method)
- Test: `tests/Feature/CourseInstructorControllerTest.php`

**Interfaces:**
- Produces: `ListAssignableInstructors::execute(Course $course): \Illuminate\Support\Collection` — Instructor/Admin-role users not already assigned to the course, each with `id, first_name, last_name, email`, ordered by `first_name`.
- The `Courses/Show` Inertia response gains `can.manage_instructors` (bool) and `assignable_instructors` (array; empty when the viewer cannot manage).

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/CourseInstructorControllerTest.php`:

```php
use Inertia\Testing\AssertableInertia as Assert;
```

(Add to the import block at the top.)

```php
it('exposes assignable instructors and the manage flag to a manager', function () {
    [$course] = courseWithInstructor();
    $candidate = userWithRole('Instructor');
    userWithRole('Student'); // not eligible, must be excluded

    $response = $this->actingAs(userWithRole('Admin'))
        ->get(route('courses.show', $course));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('can.manage_instructors', true)
        ->where('assignable_instructors', fn ($candidates) => collect($candidates)->contains('id', $candidate->id)
            && collect($candidates)->count() >= 1)
    );
});

it('hides assignable instructors from a non-manager', function () {
    [$course] = courseWithInstructor();
    userWithRole('Instructor');

    $response = $this->actingAs(userWithRole('Student'))
        ->get(route('courses.show', $course));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('can.manage_instructors', false)
        ->where('assignable_instructors', [])
    );
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter="assignable instructors and the manage flag|hides assignable instructors"`
Expected: FAIL — `assignable_instructors` / `can.manage_instructors` props are absent.

- [ ] **Step 3: Create `ListAssignableInstructors`**

Create `app/Actions/Courses/ListAssignableInstructors.php`:

```php
<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Collection;

class ListAssignableInstructors
{
    /**
     * List Instructor/Admin-role users who are not yet instructors of the course.
     *
     * @return Collection<int, User>
     */
    public function execute(Course $course): Collection
    {
        $assigned_ids = $course->instructors()->pluck('users.id');

        return User::role(['Admin', 'Instructor'])
            ->whereNotIn('id', $assigned_ids)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email']);
    }
}
```

- [ ] **Step 4: Update the controller `show` method**

In `app/Http/Controllers/CourseController.php`, add the import:

```php
use App\Actions\Courses\ListAssignableInstructors;
```

Replace the `show` method (lines ~69-77) with:

```php
    /**
     * Display the specified course.
     */
    public function show(Request $request, Course $course, LoadCourseDetails $loadCourseDetails, ListAssignableInstructors $listAssignableInstructors): Response
    {
        $can_manage_instructors = $request->user()->can('manageInstructors', $course);

        return Inertia::render('Courses/Show', [
            'course' => $loadCourseDetails->execute($course),
            'can' => [
                'update' => $request->user()->can('update', $course),
                'manage_instructors' => $can_manage_instructors,
            ],
            'assignable_instructors' => $can_manage_instructors
                ? $listAssignableInstructors->execute($course)
                : [],
        ]);
    }
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --compact tests/Feature/CourseInstructorControllerTest.php`
Expected: PASS (all). Also re-run `tests/Feature/CourseControllerTest.php` to confirm the `show` change didn't break the existing display test.

Run: `php artisan test --compact tests/Feature/CourseControllerTest.php`
Expected: PASS (all).

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Actions/Courses/ListAssignableInstructors.php app/Http/Controllers/CourseController.php tests/Feature/CourseInstructorControllerTest.php
git commit -m "feat: expose assignable instructors on course show"
```

---

### Task 7: Inline add/remove instructor UI on `Courses/Show.vue`

**Files:**
- Modify: `resources/js/Pages/Courses/Show.vue`

**Interfaces:**
- Consumes props: `can.manage_instructors` (bool), `assignable_instructors` (array of `{id, first_name, last_name, email}`), and existing `course.instructors`.
- Consumes routes: `courses.instructors.store`, `courses.instructors.destroy`.

> **Frontend note:** Activate the `inertia-vue-development` skill before implementing this task. This task has no PHPUnit/Pest test; verify by building and exercising the UI. After editing, the user must have `npm run dev`/`composer run dev` running (or run `npm run build`) to see changes.

- [ ] **Step 1: Update the `<script setup>` block**

In `resources/js/Pages/Courses/Show.vue`:

1. Add `ref` to the Vue import (line 2): `import { computed, ref } from 'vue';`
2. Add `X` and `UserPlus` to the lucide import (lines 4-7), e.g. append `X, UserPlus` to the existing icon list.
3. Remove the now-unused `UserList` import (line 12) — instructors render inline below.
4. Extend the `can` prop default and add the `assignable_instructors` prop:

```js
const props = defineProps({
    course: {
        type: Object,
        required: true,
    },
    can: {
        type: Object,
        default: () => ({ update: false, manage_instructors: false }),
    },
    assignable_instructors: {
        type: Array,
        default: () => [],
    },
});
```

5. Add the computed flag and handlers (after the existing `canManage` computed, line ~26):

```js
const canManageInstructors = computed(() => props.can.manage_instructors);

const selected_instructor_id = ref('');

const addInstructor = () => {
    if (!selected_instructor_id.value) {
        return;
    }
    router.post(
        route('courses.instructors.store', props.course.id),
        { user_id: selected_instructor_id.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selected_instructor_id.value = '';
            },
        },
    );
};

const removeInstructor = (instructor) => {
    router.delete(
        route('courses.instructors.destroy', { course: props.course.id, user: instructor.id }),
        { preserveScroll: true },
    );
};
```

- [ ] **Step 2: Replace the Instructors `CardContent` markup**

Replace the Instructors card body (lines ~196-202, the `<CardContent>` containing `<UserList .../>`) with an inline list that mirrors the Students panel and adds management controls:

```vue
          <CardContent>
            <div v-if="course.instructors && course.instructors.length > 0" class="space-y-3 max-h-96 overflow-y-auto">
              <div
                  v-for="instructor in course.instructors"
                  :key="instructor.id"
                  class="flex items-center justify-between gap-3 p-3 bg-darker-50 rounded-lg"
              >
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-primary-200 flex items-center justify-center">
                    <User class="w-5 h-5 text-primary-700" />
                  </div>
                  <div>
                    <p class="font-semibold text-darker-900">
                      {{ instructor.first_name }} {{ instructor.last_name }}
                    </p>
                    <p class="text-sm text-darker-600">{{ instructor.email }}</p>
                  </div>
                </div>
                <Button
                    v-if="canManageInstructors"
                    variant="ghost"
                    size="icon-sm"
                    class="text-destructive hover:bg-destructive/10 disabled:opacity-30"
                    :disabled="course.instructors.length === 1"
                    :aria-label="`Remove ${instructor.first_name} ${instructor.last_name}`"
                    @click="removeInstructor(instructor)"
                >
                  <X class="w-4 h-4" />
                </Button>
              </div>
            </div>
            <div v-else class="text-center py-8 text-darker-500">
              <Users class="w-10 h-10 mb-3 mx-auto" />
              <p>No instructors assigned yet</p>
            </div>

            <!-- Add instructor -->
            <div v-if="canManageInstructors && assignable_instructors.length > 0" class="mt-4 pt-4 border-t border-darker-200 flex items-center gap-2">
              <select
                  v-model="selected_instructor_id"
                  class="flex-1 rounded-md border border-darker-300 bg-white px-3 py-2 text-sm text-darker-900"
                  aria-label="Select an instructor to add"
              >
                <option value="">Select an instructor…</option>
                <option v-for="candidate in assignable_instructors" :key="candidate.id" :value="candidate.id">
                  {{ candidate.first_name }} {{ candidate.last_name }} ({{ candidate.email }})
                </option>
              </select>
              <Button :disabled="!selected_instructor_id" @click="addInstructor">
                <UserPlus class="w-4 h-4" />
                Add
              </Button>
            </div>
          </CardContent>
```

- [ ] **Step 3: Build the frontend**

Run: `npm run build`
Expected: build completes with no errors and no warning about an undefined `UserList`/unused import.

- [ ] **Step 4: Manually verify in the app**

With the dev server running (`composer run dev`), as an Admin or an assigned instructor open a course Show page and confirm:
- Each instructor row shows a remove (×) button; it is disabled when only one instructor remains.
- The "Select an instructor… / Add" control appears and adding a candidate updates the list.
- As a student (or unrelated instructor), no remove buttons or add control appear.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Courses/Show.vue
git commit -m "feat: add inline instructor management to course show page"
```

---

### Task 8: Full suite verification

**Files:** none (verification only).

- [ ] **Step 1: Run the full PHP test suite**

Run: `php artisan test --compact`
Expected: PASS (all tests green, including the existing `CourseControllerTest`, `AuthorizationTest`, and the new `CourseInstructorControllerTest`).

- [ ] **Step 2: Run Pint across the changed files**

Run: `vendor/bin/pint --dirty --format agent`
Expected: no style violations remain.

- [ ] **Step 3: Final commit if Pint changed anything**

```bash
git add -A
git commit -m "chore: pint formatting for instructor management" || echo "nothing to commit"
```

---

## Self-Review Notes

- **Spec coverage:** creator auto-assign (Task 1); ≥1 invariant enforced in `RemoveInstructor` (Task 3) + endpoint test (Task 5); eligibility = Instructor/Admin Spatie role (Task 4 validation, Task 6 candidate query); manage permission = admin or assigned instructor (Task 2 policy, enforced Task 5); endpoints `POST`/`DELETE` nested under course (Task 5); UI on Show page gated by `can_manage` (Tasks 6-7); `assignable_instructors` loaded only for managers (Task 6). All spec test cases map to Task 5/6 tests.
- **Eligibility is by Spatie role, not the `users.role` column** — tests use `userWithRole(...)`; the candidate query uses the Spatie `role` scope and the validation uses `hasAnyRole`.
- **Type consistency:** `CreateCourse::execute(array, User)`, `AssignInstructor::execute(Course, User): void`, `RemoveInstructor::execute(Course, User): void`, `ListAssignableInstructors::execute(Course): Collection`, `CoursePolicy::manageInstructors(User, Course): bool` — names/signatures match across tasks and controller call sites.
- **Decision (validation placement):** eligibility + duplicate checks live in `StoreCourseInstructorRequest` (idiomatic, surfaces `assertSessionHasErrors`) rather than inside `AssignInstructor`, which stays a thin attach. The ≥1 invariant stays in `RemoveInstructor` because the delete endpoint has no FormRequest body. This is a small, deliberate refinement of the spec's "Action rejects" wording.
