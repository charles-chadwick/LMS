# Bulk-Enroll a Group's Members Into a Course

**Date:** 2026-06-29
**Status:** Approved design

## Summary

Courses can be assigned to individual users today (via `courses_users`, with an
`is_instructor` flag). This feature lets an authorized user assign a **group** to a
course as a convenience selector: doing so enrolls the group's current members into
the course as **students**.

The assignment is a **one-time bulk action** — no `course_group` relationship is
stored. Picking a group simply enrolls its current members at that moment; later
changes to group membership do not propagate.

## Behavior

When a group is assigned to a course, for each current member of the group:

- **Has an active enrollment** in the course (student or instructor, `deleted_at IS NULL`)
  → **skip** (already added).
- **Has a soft-deleted enrollment** (previously removed) → **restore** it and set
  `is_instructor = false` (re-enroll as a student).
- **Has no enrollment row at all** → **attach** as a student (`is_instructor => false`).
- **Lacks the Student role** → **skip**. A non-student member (e.g. an instructor-role
  user) is not eligible to be added as a student. Skipping is silent; it does not fail
  the whole operation.

The operation reports the number of members actually enrolled, so the user-facing
message is honest when some members were skipped.

### Decisions (locked)

- **Assignment model:** one-time bulk action; no stored `course_group` link.
- **Enrolled role:** always students (`is_instructor = false`).
- **Previously-removed members:** re-enrolled (soft-deleted enrollment is restored).
- **Non-student-role members:** silently skipped.
- **Scope:** full vertical slice — backend action + endpoint + frontend UI.

## Components

### Action — `app/Actions/Courses/EnrollGroupMembers.php`

```php
public function execute(Course $course, Group $group): int
```

- Loads `$group->users` (current members).
- Iterates members, applying the eligibility rules above.
- Wrapped in a DB transaction.
- Returns the count of members actually enrolled (attached or restored).

Restore handling: because the `users()` relation's `detach()` hard-deletes by
default, a restorable soft-deleted row generally only exists when one was created by
other means. The action must still handle it: look for any `courses_users` row for
the user (including trashed), and if a trashed one exists, restore it with
`is_instructor = false` rather than attaching a duplicate.

### List action — `app/Actions/Courses/ListAssignableGroups.php`

```php
public function execute(?string $search = null, int $limit = 20): Collection
```

- Returns groups filtered by an optional name/description search term.
- Ordered by name, capped at `$limit`.
- No "already assigned" filtering — assignment is not persisted, so every group is
  always selectable.

### Endpoint & controller

Add two methods to the existing `CourseStudentController` (keeps student-enrollment
concerns together). Both authorize `manageStudents` on the course.

- `assignableGroups(Request, Course, ListAssignableGroups): JsonResponse` — typeahead
  source for groups.
- `storeGroup(StoreCourseGroupStudentsRequest, Course, EnrollGroupMembers): RedirectResponse`
  — resolves the group, runs the action, redirects to `courses.show` with a flash
  message: `"{n} member(s) enrolled from {group name}."` using the returned count.

### Request — `app/Http/Requests/StoreCourseGroupStudentsRequest.php`

- `authorize()`: `$this->user()->can('manageStudents', $this->route('course'))`.
- Rules: `group_id` is `required|integer|exists:groups,id`.

### Routes — `routes/web.php`

Inside the existing course route group, mirroring the student routes:

```php
// Search groups whose members can be bulk-enrolled (typeahead)
Route::get('/{course}/students/assignable-groups', [CourseStudentController::class, 'assignableGroups'])
    ->name('students.assignable-groups');

// Bulk-enroll a group's members as students
Route::post('/{course}/students/group', [CourseStudentController::class, 'storeGroup'])
    ->name('students.storeGroup');
```

### Frontend

- **New component** `resources/js/components/GroupSearchSelect.vue`, mirroring
  `UserSearchSelect.vue` but rendering group **name + description** and hitting the
  `assignable-groups` URL. (`UserSearchSelect` is too user-shaped to reuse directly.)
- **`resources/js/Pages/Courses/Show.vue`**: in the "Add student" area, add a second
  row under the existing user typeahead — a group picker plus an **"Add group"**
  button, gated by the same `canManageStudents`. On submit:
  `router.post(route('courses.students.storeGroup', course.id), { group_id })`,
  clearing the selection and relying on the redirect to refresh
  `course.students` / `students_count`.

## Authorization

All new endpoints gate on the existing `manageStudents` course policy ability (which
defers to `update`). No new policy methods needed.

## Testing

Feature tests for the action and endpoint:

- Enrolls members with no existing enrollment as students.
- Skips members with an active enrollment (student or instructor).
- Restores a soft-deleted enrollment as a student.
- Skips members lacking the Student role.
- Returns/reports the correct enrolled count when some members are skipped.
- Endpoint authorizes via `manageStudents` (forbidden for unauthorized users).
- `assignable-groups` typeahead filters by search term and respects the limit.

## Out of Scope

- Persistent course↔group assignment / ongoing membership sync.
- Choosing a role per group, or mirroring group leadership into instructor roles.
- Bulk-assigning groups as instructors.
