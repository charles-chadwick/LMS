# Manage Course Instructors — Design

**Date:** 2026-06-27
**Branch:** feature/course-pages-management
**Status:** Approved

## Goal

Provide a way to assign and remove **instructors** on a course. A course must
always have **at least one** instructor. This is the first slice of a broader
"add users to courses" capability; students and other roles come later.

## Background

The infrastructure already exists:

- `courses_users` pivot table with an `is_instructor` boolean flag and full
  audit columns (`created_by_id`, etc.).
- `Course::instructors()` / `Course::students()` relationships scope the pivot
  by `is_instructor`.
- `Courses/Show.vue` already *displays* an Instructors panel.
- `CoursePolicy::update` already encodes "admin or an instructor of this course".

What is missing is any way to *assign* instructors. Currently `CreateCourse`
attaches nobody, so a new course has zero instructors.

## Decisions

- **First instructor:** the course creator is auto-assigned as an instructor on
  create. Guarantees ≥1 with no extra creation UI.
- **UI placement:** inline on the existing `Courses/Show.vue` Instructors panel.
  (A dedicated `/courses/{course}/instructors` page was considered and deferred —
  more scaffolding than this slice needs; extract later if roles grow.)
- **Eligibility:** only users whose global role is `Instructor` or `Admin` may be
  assigned as a course instructor.
- **Who can manage:** admins, plus instructors already assigned to that course
  (mirrors the existing `update` policy).

## Core invariant

**A course always has ≥1 instructor.** Enforced in the Action layer (not just the
UI):

- `CreateCourse` attaches the creator with `is_instructor = true`.
- `RemoveInstructor` refuses to detach the last remaining instructor.

## Endpoints & routes

Nested under a course, `auth` middleware (consistent with existing course routes):

- `POST   /courses/{course}/instructors`        → `store`  (body: `user_id`)
- `DELETE /courses/{course}/instructors/{user}` → `destroy`

Both authorized by a new `CoursePolicy::manageInstructors(User $user, Course $course): bool`
ability that mirrors `update`: admin **or** an instructor already on the course.

## Actions (`app/Actions/Courses/`)

- `AssignInstructor::execute(Course $course, User $user): void`
  - Rejects users without the `Instructor` or `Admin` role.
  - No-op / rejects if the user is already an instructor of the course.
  - Attaches with `is_instructor = true`.
- `RemoveInstructor::execute(Course $course, User $user): void`
  - Throws a validation exception (surfaced as a flash/validation error) if the
    user is the course's last instructor.
  - Otherwise detaches the user from the course.

`CreateCourse` is updated to attach the authenticated creator as an instructor.

## Controller & validation

A thin `CourseInstructorController`:

- `store(StoreCourseInstructorRequest $request, Course $course, AssignInstructor $action)`
- `destroy(Course $course, User $user, RemoveInstructor $action)`

Each authorizes via the `manageInstructors` policy ability, resolves the Action
via method injection, passes data to `execute()`, and returns a `redirect()` back
to the course Show page with a flash message.

`StoreCourseInstructorRequest` validates `user_id` exists and resolves to an
eligible (Instructor/Admin) user.

## UI (`Courses/Show.vue`)

The Instructors panel gains, **only when `can_manage` is true**:

- A remove (×) control on each instructor row — hidden/disabled on the last
  remaining instructor.
- An "Add instructor" control: a `<select>` of eligible candidates submitting via
  Inertia.

`LoadCourseDetails` passes to the page:

- `assignable_instructors` — Instructor/Admin-role users not already assigned;
  loaded **only when the viewer can manage**.
- `can_manage` — boolean from the `manageInstructors` policy.

## Testing

Feature tests:

- Creator is auto-assigned as instructor on course create.
- Admin can add an instructor.
- A course instructor can add another instructor.
- A non-manager (unrelated instructor / student) gets 403 on add and remove.
- Adding a Student-role user is rejected (validation).
- Adding an already-assigned user is rejected.
- Removing the last instructor is blocked (invariant holds).
- Removing a non-last instructor succeeds.

## Out of scope

- Assigning students or any non-instructor role.
- Distinguishing instructor sub-roles (primary, TA, etc.).
- A dedicated instructor-management page/route.
