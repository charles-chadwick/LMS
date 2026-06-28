# Course Student Enrollment Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let course managers enroll and remove students on a course via the existing `UserSelect` component on `Courses/Show.vue`, bringing the read-only Students section to full parity with the Instructors workflow.

**Architecture:** Mirror the existing instructor stack (policy ability → actions → form request → controller → routes → controller props → Vue UI → tests). Students are stored in the same `courses_users` pivot with `is_instructor = false`. A new `manageStudents` policy ability gates the workflow. No minimum-student guard. Eligible students are `Student`-role users excluding existing students and the course's instructors.

**Tech Stack:** Laravel 13, PHP 8.4, Inertia v3, Vue 3, Pest 4, spatie/laravel-permission (roles).

## Global Constraints

- Naming (project + global standards): PHP variables `snake_case`, methods `camelCase`, classes `TitleCase`. Vue refs/props `snake_case`. Use full descriptive names — no abbreviations.
- Controllers stay thin: business logic lives in single-purpose Action classes under `app/Actions/Courses/`, each with one public `execute()` method and explicit types.
- Use `php artisan make:` generators with `--no-interaction`. Curly braces on all control structures. Explicit return types and param type hints. PHPDoc blocks over inline comments.
- After modifying any PHP file, run `vendor/bin/pint --dirty --format agent` before committing.
- Tests are Pest only (`it()`/`expect()`). Use the existing `userWithRole('...')` helper from `tests/Pest.php` and `LazilyRefreshDatabase`. The seeded roles are `Admin`, `Instructor`, `Student`.
- Run the minimal filtered test after each task: `php artisan test --compact --filter=...`.
- Frontend changes require `npm run build` / `npm run dev` to appear — note this to the user; do not assume the UI updated.

---

### Task 1: `manageStudents` policy ability + coverage

**Files:**
- Modify: `app/Policies/CoursePolicy.php` (add method after `manageInstructors`, ~line 48)
- Test: `tests/Feature/CourseStudentControllerTest.php` (create)

**Interfaces:**
- Produces: `CoursePolicy::manageStudents(User $user, Course $course): bool` — true for admins and instructors who teach the course (delegates to `update()`). Used by Tasks 4, 5, 6.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/CourseStudentControllerTest.php`:

```php
<?php

use App\Models\Course;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('authorizes admins and assigned instructors to manage students', function () {
    $course = Course::factory()->create();
    $instructor = userWithRole('Instructor');
    $course->instructors()->attach($instructor, ['is_instructor' => true]);

    $admin = userWithRole('Admin');
    $other_instructor = userWithRole('Instructor');
    $student = userWithRole('Student');

    expect($admin->can('manageStudents', $course))->toBeTrue()
        ->and($instructor->can('manageStudents', $course))->toBeTrue()
        ->and($other_instructor->can('manageStudents', $course))->toBeFalse()
        ->and($student->can('manageStudents', $course))->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter='authorizes admins and assigned instructors to manage students'`
Expected: FAIL — no ability named `manageStudents` registered.

- [ ] **Step 3: Add the policy method**

In `app/Policies/CoursePolicy.php`, after the `manageInstructors` method:

```php
    /**
     * Managing students follows the same rule as updating.
     */
    public function manageStudents(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter='authorizes admins and assigned instructors to manage students'`
Expected: PASS

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Policies/CoursePolicy.php tests/Feature/CourseStudentControllerTest.php
git commit -m "feat: add manageStudents course policy ability"
```

---

### Task 2: `AssignStudent` and `RemoveStudent` actions

**Files:**
- Create: `app/Actions/Courses/AssignStudent.php`
- Create: `app/Actions/Courses/RemoveStudent.php`
- Test: `tests/Feature/CourseStudentControllerTest.php` (append)

**Interfaces:**
- Produces:
  - `AssignStudent::execute(Course $course, User $user): void` — attaches the user to `students()` with `is_instructor = false`.
  - `RemoveStudent::execute(Course $course, User $user): void` — detaches the user from the course. No minimum-student guard.
- Consumes: `Course::students()` relationship (already exists).

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/CourseStudentControllerTest.php` (add the two `use` imports at the top of the file, below the existing ones):

```php
use App\Actions\Courses\AssignStudent;
use App\Actions\Courses\RemoveStudent;
```

```php
it('attaches a user as a student via the AssignStudent action', function () {
    $course = Course::factory()->create();
    $user = userWithRole('Student');

    app(AssignStudent::class)->execute($course, $user);

    expect($course->students()->whereKey($user->id)->exists())->toBeTrue();
});

it('detaches a student via the RemoveStudent action', function () {
    $course = Course::factory()->create();
    $student = userWithRole('Student');
    $course->students()->attach($student, ['is_instructor' => false]);

    app(RemoveStudent::class)->execute($course, $student);

    expect($course->students()->whereKey($student->id)->exists())->toBeFalse();
});

it('removes the last student without error', function () {
    $course = Course::factory()->create();
    $student = userWithRole('Student');
    $course->students()->attach($student, ['is_instructor' => false]);

    app(RemoveStudent::class)->execute($course, $student);

    expect($course->students()->count())->toBe(0);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter='AssignStudent action|RemoveStudent action|removes the last student'`
Expected: FAIL — `Class "App\Actions\Courses\AssignStudent" not found`.

- [ ] **Step 3: Create the actions**

`app/Actions/Courses/AssignStudent.php`:

```php
<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\User;

class AssignStudent
{
    /**
     * Assign a user to the course as a student.
     */
    public function execute(Course $course, User $user): void
    {
        $course->students()->attach($user, ['is_instructor' => false]);
    }
}
```

`app/Actions/Courses/RemoveStudent.php`:

```php
<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\User;

class RemoveStudent
{
    /**
     * Remove a student from the course. A course may have zero students.
     */
    public function execute(Course $course, User $user): void
    {
        $course->students()->detach($user);
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact --filter='AssignStudent action|RemoveStudent action|removes the last student'`
Expected: PASS (3 tests)

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Actions/Courses/AssignStudent.php app/Actions/Courses/RemoveStudent.php tests/Feature/CourseStudentControllerTest.php
git commit -m "feat: add AssignStudent and RemoveStudent actions"
```

---

### Task 3: `ListAssignableStudents` action

**Files:**
- Create: `app/Actions/Courses/ListAssignableStudents.php`
- Test: `tests/Unit/ListAssignableStudentsTest.php` (create)

**Interfaces:**
- Produces: `ListAssignableStudents::execute(Course $course): Collection<int, User>` — `Student`-role users, excluding existing students AND the course's instructors, ordered by `first_name`, eager-loading `media`, selecting `id, first_name, last_name, email`. Used by Tasks 4 and 6.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/ListAssignableStudentsTest.php`:

```php
<?php

use App\Actions\Courses\ListAssignableStudents;
use App\Models\Course;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('lists Student-role users excluding enrolled students and course instructors', function () {
    $course = Course::factory()->create();

    $candidate = userWithRole('Student');
    $enrolled_student = userWithRole('Student');
    $course->students()->attach($enrolled_student, ['is_instructor' => false]);

    $course_instructor = userWithRole('Student'); // a student who also instructs this course
    $course->instructors()->attach($course_instructor, ['is_instructor' => true]);

    $non_student = userWithRole('Instructor');

    $assignable = app(ListAssignableStudents::class)->execute($course);
    $ids = $assignable->pluck('id');

    expect($ids)->toContain($candidate->id)
        ->and($ids)->not->toContain($enrolled_student->id)
        ->and($ids)->not->toContain($course_instructor->id)
        ->and($ids)->not->toContain($non_student->id);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter='lists Student-role users excluding'`
Expected: FAIL — `Class "App\Actions\Courses\ListAssignableStudents" not found`.

- [ ] **Step 3: Create the action**

`app/Actions/Courses/ListAssignableStudents.php`:

```php
<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Collection;

class ListAssignableStudents
{
    /**
     * List Student-role users who are neither enrolled in nor instructing the course.
     *
     * @return Collection<int, User>
     */
    public function execute(Course $course): Collection
    {
        $student_ids = $course->students()->pluck('users.id');
        $instructor_ids = $course->instructors()->pluck('users.id');
        $excluded_ids = $student_ids->merge($instructor_ids);

        return User::whereHas('roles', fn ($query) => $query->where('name', 'Student'))
            ->whereNotIn('id', $excluded_ids)
            ->with('media')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email']);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter='lists Student-role users excluding'`
Expected: PASS

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Actions/Courses/ListAssignableStudents.php tests/Unit/ListAssignableStudentsTest.php
git commit -m "feat: add ListAssignableStudents action"
```

---

### Task 4: `StoreCourseStudentRequest` form request

**Files:**
- Create: `app/Http/Requests/StoreCourseStudentRequest.php`

**Interfaces:**
- Produces: `StoreCourseStudentRequest` — authorizes via `manageStudents`; validates `user_id` is required, integer, exists in `users`, holds the `Student` role, and is not already a student of the course. Consumed by Task 5's controller. (Behavior is covered by the controller feature tests in Task 5; no separate test task.)

- [ ] **Step 1: Generate the request**

Run: `php artisan make:request StoreCourseStudentRequest --no-interaction`

- [ ] **Step 2: Replace its contents**

`app/Http/Requests/StoreCourseStudentRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCourseStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manageStudents', $this->route('course'));
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

                    if (! $user->hasRole('Student')) {
                        $fail('The selected user must be a student.');
                    } elseif ($course->students()->whereKey($value)->exists()) {
                        $fail('This user is already a student of the course.');
                    }
                },
            ],
        ];
    }
}
```

- [ ] **Step 3: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Requests/StoreCourseStudentRequest.php
git commit -m "feat: add StoreCourseStudentRequest validation"
```

---

### Task 5: `CourseStudentController` + routes + endpoint tests

**Files:**
- Create: `app/Http/Controllers/CourseStudentController.php`
- Modify: `routes/web.php` (add two routes inside the courses group, after the instructor routes ~line 50)
- Test: `tests/Feature/CourseStudentControllerTest.php` (append endpoint tests)

**Interfaces:**
- Consumes: `StoreCourseStudentRequest` (Task 4), `AssignStudent` / `RemoveStudent` (Task 2), `manageStudents` (Task 1).
- Produces: named routes `courses.students.store` (POST `/{course}/students`) and `courses.students.destroy` (DELETE `/{course}/students/{user}`).

- [ ] **Step 1: Write the failing endpoint tests**

Append to `tests/Feature/CourseStudentControllerTest.php`. Add this helper at the bottom of the file:

```php
/**
 * Create a course that already has one assigned instructor.
 *
 * @return array{0: Course, 1: User}
 */
function courseWithManager(): array
{
    $course = Course::factory()->create();
    $instructor = userWithRole('Instructor');
    $course->instructors()->attach($instructor, ['is_instructor' => true]);

    return [$course, $instructor];
}
```

And these tests:

```php
it('lets an admin enroll a student', function () {
    [$course] = courseWithManager();
    $student = userWithRole('Student');

    $response = $this->actingAs(userWithRole('Admin'))
        ->post(route('courses.students.store', $course), ['user_id' => $student->id]);

    $response->assertRedirect(route('courses.show', $course));
    $response->assertSessionHas('success');
    expect($course->students()->whereKey($student->id)->exists())->toBeTrue();
});

it('lets an assigned instructor enroll a student', function () {
    [$course, $instructor] = courseWithManager();
    $student = userWithRole('Student');

    $response = $this->actingAs($instructor)
        ->post(route('courses.students.store', $course), ['user_id' => $student->id]);

    $response->assertRedirect(route('courses.show', $course));
    expect($course->students()->count())->toBe(1);
});

it('forbids a non-manager from enrolling a student', function () {
    [$course] = courseWithManager();
    $student = userWithRole('Student');

    $response = $this->actingAs(userWithRole('Instructor'))
        ->post(route('courses.students.store', $course), ['user_id' => $student->id]);

    $response->assertForbidden();
    expect($course->students()->whereKey($student->id)->exists())->toBeFalse();
});

it('rejects enrolling a user without the Student role', function () {
    [$course] = courseWithManager();
    $non_student = userWithRole('Instructor');

    $response = $this->actingAs(userWithRole('Admin'))
        ->post(route('courses.students.store', $course), ['user_id' => $non_student->id]);

    $response->assertSessionHasErrors('user_id');
    expect($course->students()->whereKey($non_student->id)->exists())->toBeFalse();
});

it('rejects enrolling an already-enrolled student', function () {
    [$course] = courseWithManager();
    $student = userWithRole('Student');
    $course->students()->attach($student, ['is_instructor' => false]);

    $response = $this->actingAs(userWithRole('Admin'))
        ->post(route('courses.students.store', $course), ['user_id' => $student->id]);

    $response->assertSessionHasErrors('user_id');
    expect($course->students()->count())->toBe(1);
});

it('lets an admin remove a student', function () {
    [$course] = courseWithManager();
    $student = userWithRole('Student');
    $course->students()->attach($student, ['is_instructor' => false]);

    $response = $this->actingAs(userWithRole('Admin'))
        ->delete(route('courses.students.destroy', ['course' => $course, 'user' => $student]));

    $response->assertRedirect(route('courses.show', $course));
    expect($course->students()->whereKey($student->id)->exists())->toBeFalse();
});

it('forbids a non-manager from removing a student', function () {
    [$course] = courseWithManager();
    $student = userWithRole('Student');
    $course->students()->attach($student, ['is_instructor' => false]);

    $response = $this->actingAs(userWithRole('Instructor'))
        ->delete(route('courses.students.destroy', ['course' => $course, 'user' => $student]));

    $response->assertForbidden();
    expect($course->students()->whereKey($student->id)->exists())->toBeTrue();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter='enroll|remove a student|removing a student'`
Expected: FAIL — route `courses.students.store` not defined.

- [ ] **Step 3: Create the controller**

`app/Http/Controllers/CourseStudentController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Actions\Courses\AssignStudent;
use App\Actions\Courses\RemoveStudent;
use App\Http\Requests\StoreCourseStudentRequest;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class CourseStudentController extends Controller
{
    /**
     * Enroll a student in the course.
     */
    public function store(StoreCourseStudentRequest $request, Course $course, AssignStudent $assignStudent): RedirectResponse
    {
        $user = User::findOrFail($request->validated()['user_id']);

        $assignStudent->execute($course, $user);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Student added successfully.');
    }

    /**
     * Remove a student from the course.
     */
    public function destroy(Course $course, User $user, RemoveStudent $removeStudent): RedirectResponse
    {
        $this->authorize('manageStudents', $course);

        $removeStudent->execute($course, $user);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Student removed successfully.');
    }
}
```

- [ ] **Step 4: Register the routes**

In `routes/web.php`, add `use App\Http\Controllers\CourseStudentController;` with the other controller imports, then inside the courses route group, immediately after the `instructors.destroy` route:

```php
    // Enroll a student in a course
    Route::post('/{course}/students', [CourseStudentController::class, 'store'])->name('students.store');

    // Remove a student from a course
    Route::delete('/{course}/students/{user}', [CourseStudentController::class, 'destroy'])->name('students.destroy');
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --compact --filter='enroll|remove a student|removing a student'`
Expected: PASS (7 tests)

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/CourseStudentController.php routes/web.php tests/Feature/CourseStudentControllerTest.php
git commit -m "feat: add course student enroll/remove endpoints"
```

---

### Task 6: Expose `manage_students` + `assignable_students` from `CourseController::show`

**Files:**
- Modify: `app/Http/Controllers/CourseController.php` (the `show` method, ~lines 69–83)
- Test: `tests/Feature/CourseStudentControllerTest.php` (append)

**Interfaces:**
- Consumes: `ListAssignableStudents` (Task 3), `manageStudents` (Task 1).
- Produces: Inertia props `can.manage_students` (bool) and `assignable_students` (array, empty when not permitted) on the `Courses/Show` page. Consumed by Task 7.

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/CourseStudentControllerTest.php`. Add to the top-of-file imports:

```php
use Inertia\Testing\AssertableInertia as Assert;
```

```php
it('exposes assignable students and the manage flag to a manager', function () {
    [$course] = courseWithManager();
    $candidate = userWithRole('Student');
    $enrolled = userWithRole('Student');
    $course->students()->attach($enrolled, ['is_instructor' => false]);

    $response = $this->actingAs(userWithRole('Admin'))
        ->get(route('courses.show', $course));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('can.manage_students', true)
        ->where('assignable_students', fn ($candidates) => collect($candidates)->contains('id', $candidate->id)
            && ! collect($candidates)->contains('id', $enrolled->id))
    );
});

it('hides assignable students from a non-manager', function () {
    [$course] = courseWithManager();
    userWithRole('Student');

    $response = $this->actingAs(userWithRole('Student'))
        ->get(route('courses.show', $course));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('can.manage_students', false)
        ->where('assignable_students', [])
    );
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter='assignable students'`
Expected: FAIL — `can.manage_students` / `assignable_students` props are missing.

- [ ] **Step 3: Update the controller**

In `app/Http/Controllers/CourseController.php`, add the import `use App\Actions\Courses\ListAssignableStudents;` alongside the other action imports. Then update the `show` method signature and body:

```php
    public function show(Request $request, Course $course, LoadCourseDetails $loadCourseDetails, ListAssignableInstructors $listAssignableInstructors, ListAssignableStudents $listAssignableStudents): Response
    {
        $can_manage_instructors = $request->user()->can('manageInstructors', $course);
        $can_manage_students = $request->user()->can('manageStudents', $course);

        return Inertia::render('Courses/Show', [
            'course' => $loadCourseDetails->execute($course),
            'can' => [
                'update' => $request->user()->can('update', $course),
                'manage_instructors' => $can_manage_instructors,
                'manage_students' => $can_manage_students,
            ],
            'assignable_instructors' => $can_manage_instructors
                ? $listAssignableInstructors->execute($course)
                : [],
            'assignable_students' => $can_manage_students
                ? $listAssignableStudents->execute($course)
                : [],
        ]);
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact --filter='assignable students'`
Expected: PASS (2 tests)

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/CourseController.php tests/Feature/CourseStudentControllerTest.php
git commit -m "feat: pass student management props to course show page"
```

---

### Task 7: `Courses/Show.vue` — student enroll/remove UI

**Files:**
- Modify: `resources/js/Pages/Courses/Show.vue` (props block ~lines 16–28; script ~lines 33–57; Students card ~lines 280–309; student row ~lines 288–301; lucide import ~line 6)

**Interfaces:**
- Consumes: props `can.manage_students`, `assignable_students` (Task 6); routes `courses.students.store` / `courses.students.destroy` (Task 5); existing `UserSelect`, `Button`, `Avatar` components.
- Produces: UI only — no downstream consumers.

This task has no automated test (Vue view wiring); it is verified manually in Step 6. Keep it as one task because the deliverable is the visible student-management UI.

- [ ] **Step 1: Add the `assignable_students` prop**

In the `defineProps` object, after the `assignable_instructors` prop, add:

```js
    assignable_students: {
        type: Array,
        default: () => [],
    },
```

And update the `can` prop default to include the new flag:

```js
    can: {
        type: Object,
        default: () => ({ update: false, manage_instructors: false, manage_students: false }),
    },
```

- [ ] **Step 2: Add the script logic**

After the `removeInstructor` function (~line 58), add:

```js
const canManageStudents = computed(() => props.can.manage_students);

const selected_student_id = ref('');

const addStudent = () => {
    if (!selected_student_id.value) {
        return;
    }
    router.post(
        route('courses.students.store', props.course.id),
        { user_id: selected_student_id.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selected_student_id.value = '';
            },
        },
    );
};

const removeStudent = (student) => {
    router.delete(
        route('courses.students.destroy', { course: props.course.id, user: student.id }),
        { preserveScroll: true },
    );
};
```

- [ ] **Step 3: Update the student row to include a remove button**

Replace the existing student `v-for` row block (the `<div v-for="student in course.students" ...>` and its inner content) with a `justify-between` layout that adds the remove button:

```vue
              <div
                  v-for="student in course.students"
                  :key="student.id"
                  class="flex items-center justify-between gap-3 p-3 bg-darker-50 rounded-lg"
              >
                <div class="flex items-center gap-3">
                  <Avatar :user="student" variant="accent" />
                  <div>
                    <p class="font-semibold text-darker-900">
                      {{ student.first_name }} {{ student.last_name }}
                    </p>
                    <p class="text-sm text-darker-600">{{ student.email }}</p>
                  </div>
                </div>
                <Button
                    v-if="canManageStudents"
                    variant="ghost"
                    size="icon-sm"
                    class="text-destructive hover:bg-destructive/10"
                    :aria-label="`Remove ${student.first_name} ${student.last_name}`"
                    @click="removeStudent(student)"
                >
                  <X class="w-4 h-4" />
                </Button>
              </div>
```

- [ ] **Step 4: Add the enroll control to the Students card**

Inside the Students `<CardContent>`, immediately after the `v-else` "No students enrolled yet" block and before `</CardContent>`, add:

```vue
            <!-- Add student -->
            <div v-if="canManageStudents && assignable_students.length > 0" class="mt-4 pt-4 border-t border-darker-200 flex items-center gap-2">
              <div class="flex-1">
                <UserSelect
                    v-model="selected_student_id"
                    :users="assignable_students"
                    variant="accent"
                    placeholder="Select a student…"
                />
              </div>
              <Button :disabled="!selected_student_id" @click="addStudent">
                <UserPlus class="w-4 h-4" />
                Add
              </Button>
            </div>
```

(`X`, `UserPlus`, `UserSelect`, `Button`, `Avatar`, `computed`, `ref`, `router` are all already imported.)

- [ ] **Step 5: Build the frontend**

Run: `npm run build`
Expected: build succeeds with no errors referencing `Show.vue`.

- [ ] **Step 6: Manual verification**

Ask the user to load a course Show page as an admin/assigned instructor and confirm: the Students card shows a "Select a student…" picker + Add button (only listing Student-role users who aren't already enrolled or instructing); adding enrolls them; the remove (X) button detaches a student; and a non-manager sees neither the picker nor remove buttons. Note that `npm run dev` may be needed if they don't see changes.

- [ ] **Step 7: Commit**

```bash
git add resources/js/Pages/Courses/Show.vue
git commit -m "feat: add student enroll/remove UI to course show page"
```

---

### Task 8: Full suite verification

- [ ] **Step 1: Run the course student tests together**

Run: `php artisan test --compact tests/Feature/CourseStudentControllerTest.php tests/Unit/ListAssignableStudentsTest.php`
Expected: all PASS.

- [ ] **Step 2: Run the full suite (with user approval)**

Per project convention, ask the user whether to run the entire suite, then:
Run: `php artisan test --compact`
Expected: all PASS — confirm no regressions in `CourseInstructorControllerTest` or `CourseController` tests.

---

## Self-Review

- **Spec coverage:** `manageStudents` ability → Task 1. `ListAssignableStudents` (excludes students + instructors, Student role) → Task 3. `AssignStudent` → Task 2. `RemoveStudent` no guard → Task 2. `StoreCourseStudentRequest` → Task 4. `CourseStudentController` + routes → Task 5. Controller props → Task 6. Vue UI (UserSelect add + remove) → Task 7. Feature + unit tests → Tasks 1–6, 8. All spec sections mapped.
- **Placeholder scan:** No TBD/TODO; every code step contains full code.
- **Type consistency:** `execute(Course, User): void` for Assign/Remove; `execute(Course): Collection` for the list action; route names `courses.students.store` / `courses.students.destroy`; props `can.manage_students` / `assignable_students`; refs `selected_student_id`; methods `addStudent` / `removeStudent` / `canManageStudents` — consistent across Tasks 2–7. Pivot uses `is_instructor => false` consistently.
