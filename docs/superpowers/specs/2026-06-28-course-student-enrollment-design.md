# Course Student Enrollment — Design

**Date:** 2026-06-28

## Goal

Add the ability to enroll and remove **students** on a course, surfaced through the
existing `UserSelect` component on the `Courses/Show.vue` page. This brings the
read-only Students section to full parity with the already-implemented Instructors
workflow (UserSelect + Add button, per-row Remove button, routes, actions, request
validation, and tests).

## Decisions

- **Scope:** Add **and** Remove — full parity with the instructor workflow.
- **Eligible students:** Users holding the `Student` role, excluding both users
  already enrolled as students in the course **and** users who are instructors of
  the course (no dual student/instructor on a single course).
- **Removal guard:** **None.** A course may have zero students; the last student can
  be removed freely. (Contrast: instructors require keeping at least one.)
- **Permission:** A **separate** `manageStudents` policy ability (not reusing
  `manageInstructors`), so the two can diverge later. Its rule currently mirrors
  `manageInstructors` → `update()` (admins, or instructors who teach the course).

## Current State (reference)

- Pivot table `courses_users` with `is_instructor` boolean distinguishes students
  (`false`) from instructors (`true`).
- `Course::students()` → `users()->wherePivot('is_instructor', false)`.
- `Courses/Show.vue` Students card (~lines 280–309) is **display-only** today.
- Instructor stack to mirror: `CourseInstructorController`, `AssignInstructor`,
  `RemoveInstructor`, `ListAssignableInstructors`, `StoreCourseInstructorRequest`,
  `CoursePolicy::manageInstructors`, routes `instructors.store` / `instructors.destroy`.

## Components

### 1. Authorization — `CoursePolicy::manageStudents`

```php
/**
 * Managing students follows the same rule as updating.
 */
public function manageStudents(User $user, Course $course): bool
{
    return $this->update($user, $course);
}
```

### 2. Actions (`app/Actions/Courses/`)

- **`ListAssignableStudents`** — returns `Student`-role users excluding existing
  students and the course's instructors:

  ```php
  $student_ids    = $course->students()->pluck('users.id');
  $instructor_ids = $course->instructors()->pluck('users.id');
  $excluded_ids   = $student_ids->merge($instructor_ids);

  return User::whereHas('roles', fn ($query) => $query->where('name', 'Student'))
      ->whereNotIn('id', $excluded_ids)
      ->with('media')
      ->orderBy('first_name')
      ->get(['id', 'first_name', 'last_name', 'email']);
  ```

- **`AssignStudent`** — `$course->students()->attach($user, ['is_instructor' => false]);`
- **`RemoveStudent`** — `$course->students()->detach($user);` (no guard).

### 3. Request — `StoreCourseStudentRequest`

- `authorize()` → `$this->user()->can('manageStudents', $this->route('course'))`.
- Validates `user_id`: required, integer, exists in `users`, holds the `Student`
  role, and is not already a student of the course (mirror the duplicate-check
  approach used by `StoreCourseInstructorRequest`).

### 4. Controller — `CourseStudentController`

Structured identically to `CourseInstructorController`:

- `store(StoreCourseStudentRequest, Course, AssignStudent)` → flash
  `'Student added successfully.'`
- `destroy(Course, User, RemoveStudent)` → `authorize('manageStudents', $course)`,
  flash `'Student removed successfully.'`

### 5. Routes (`routes/web.php`, courses group)

```php
Route::post('/{course}/students', [CourseStudentController::class, 'store'])->name('students.store');
Route::delete('/{course}/students/{user}', [CourseStudentController::class, 'destroy'])->name('students.destroy');
```

### 6. Controller wiring — `CourseController::show`

Add alongside the instructor props:

```php
$can_manage_students = $request->user()->can('manageStudents', $course);
// 'can' => [ ..., 'manage_students' => $can_manage_students ]
// 'assignable_students' => $can_manage_students ? $listAssignableStudents->execute($course) : []
```

### 7. Frontend — `Courses/Show.vue`

- `const canManageStudents = computed(() => props.can.manage_students);`
- `const selected_student_id = ref('');`
- `addStudent()` — posts `route('courses.students.store', course.id)` with
  `{ user_id: selected_student_id }`, `preserveScroll`, resets the ref on success.
- `removeStudent(user)` — `router.delete(route('courses.students.destroy', [course.id, user.id]), { preserveScroll: true })`.
- In the Students card: a `UserSelect` (`variant="accent"`, placeholder
  "Select a student…") + Add button gated on
  `canManageStudents && assignable_students.length > 0`; a remove button
  (`UserMinus`) on each enrolled student row gated on `canManageStudents`.

## Data Flow

1. `show()` passes `assignable_students` + `can.manage_students` to the page.
2. Enroll: select user → Add → `POST students.store` → `AssignStudent` attaches
   pivot (`is_instructor = false`) → redirect back to show → list refreshes.
3. Remove: row remove button → `DELETE students.destroy` → `RemoveStudent` detaches
   → redirect back to show.

## Error Handling

- Unauthorized enroll/remove → 403 via `manageStudents`.
- Duplicate enroll, non-existent user, or non-Student user → 422 from
  `StoreCourseStudentRequest`.
- No minimum-student constraint; removal always succeeds when authorized.

## Testing

- **Feature** (`CourseStudentController`): enroll succeeds and creates the pivot row
  with `is_instructor = false`; remove detaches; non-manager receives 403 on both;
  validation rejects a duplicate student and a non-Student user.
- **Unit** (`ListAssignableStudents`): excludes existing students and course
  instructors; includes only `Student`-role users; ordered by `first_name`.
- Mirror existing instructor test coverage and run with `php artisan test --compact`.
