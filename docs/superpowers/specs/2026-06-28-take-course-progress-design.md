# Take a Course with Progress Tracking — Design

**Date:** 2026-06-28
**Status:** Approved (design); pending implementation plan

## Summary

Enable a logged-in student who is enrolled in a Published course to "take" that
course by working through its pages in order, marking each page complete, and
tracking overall progress. When the student completes the final published page,
the course is marked complete and a printable completion certificate becomes
available.

This builds on existing structures: a `Course` already has ordered `Page`
records (flat list, HTML content), students are enrolled via the
`courses_users` pivot (`is_instructor = false`), and an unused `user_progress`
table already exists (`user_id`, `course_id`, `page_id`).

## Product decisions (from brainstorming)

- **Completion mechanic:** explicit "Mark complete & continue" button that
  records the current page as complete and auto-advances to the next incomplete
  page (a guided flow).
- **Learner UI:** a dedicated player/page route, separate from the existing
  `Courses/Show.vue`, which stays the management/overview view.
- **Navigation:** sequential gating — a page is locked until all earlier
  published pages are completed. Enforced on both frontend and backend.
- **Visibility scope:** published only — a course must be `Published` to be
  taken, and only `Published` pages are part of the sequence and count toward
  progress. Draft/Archived pages are excluded.
- **Completion:** derived from progress (all published pages done = 100%), plus
  a `completed_at` record on the enrollment and a viewable/printable completion
  certificate.
- **Progress storage:** Approach A — derive all progress state from
  `user_progress` rows (single source of truth); do not denormalize counts onto
  the pivot.

## Architecture

Follows existing conventions: thin controllers that resolve an Action via method
injection, pass validated data to a single `execute()`, and return the response.
Business logic lives in Action classes under `app/Actions/Courses/`.

### Data model

- **`Page` model**
  - Cast `status` to `App\Enums\CourseStatus` (currently a raw string; the page
    factory already uses `CourseStatus` values). This removes the magic string
    when filtering by status, per project conventions.
  - Add a `published()` query scope: `where('status', CourseStatus::Published)`.
  - Note: `Page` stores `Draft`/`Published`/`Archived` strings today, so the
    cast is value-compatible and does not change serialized output.

- **`user_progress` table** — one row per completed page.
  - New migration adds a composite unique index on `(user_id, page_id)`.
  - All writes go through `firstOrCreate(['user_id', 'course_id', 'page_id'])`
    so completing a page more than once is a no-op.
  - Columns already present: `user_id`, `course_id`, `page_id`, timestamps,
    soft deletes, audit columns. No schema change beyond the index.

- **`courses_users` pivot** — add nullable `completed_at` timestamp (new
  migration). The `Course::students()` / `Course::users()` relations gain
  `withPivot('completed_at')`. It is stamped exactly once, when the student
  completes the final published page, and is the authoritative completion date
  used by the certificate.

### Backend logic (Action classes in `app/Actions/Courses/`)

- **`LoadCoursePlayer`** — input: `Course`, `User`, optional requested
  `Page`. Returns the player payload:
  - `pages`: published pages ordered by `order`, each with `id`, `title`,
    `order`, `is_completed`, `is_locked` (locked = at least one earlier
    published page is not completed).
  - `current_page`: the requested page if provided and unlocked, otherwise the
    first incomplete published page (or the last page if all complete).
  - `progress`: `{ completed_count, total_count, percent }` where `percent` is
    integer-rounded; `total_count` is the number of published pages.
  - `is_complete`: `total_count > 0 && completed_count === total_count`.
  - `completed_at`: from the student's pivot row (nullable).

- **`CompletePage`** — input: `User`, `Course`, `Page`. Validates:
  - the user is an enrolled student of the course,
  - the page belongs to the course and is `Published`,
  - all earlier published pages (by `order`) are already completed
    (server-side sequential gating, independent of the UI).

  Then records progress idempotently via `firstOrCreate`. If, after this, every
  published page is complete and the pivot `completed_at` is null, it stamps
  `completed_at = now()`. Returns the next incomplete page id (or null when the
  course is now complete).

  A page that is already complete but whose later pages are not yet done still
  resolves the next incomplete page so the flow does not dead-end.

### Authorization (`CoursePolicy`)

- **`take(User, Course): bool`** — true only when the user is an enrolled
  student (`is_instructor = false`) **and** the course status is `Published`.
  Gates both the player view and `CompletePage`.
- **`viewCertificate(User, Course): bool`** — true only when that student's
  pivot `completed_at` is set.
- Instructors and admins are not "takers"; they continue to preview content via
  the existing page views. They are not granted `take`.

### Routes & controllers

All under the authenticated web middleware group, alongside existing course
routes in `routes/web.php`.

- **`CourseLearnController`**
  - `GET /courses/{course}/learn` — authorize `take`; render `Courses/Learn`
    with `LoadCoursePlayer` (no specific page → first incomplete).
  - `GET /courses/{course}/learn/{page}` — authorize `take`; render
    `Courses/Learn` for a specific (unlocked) page.
  - `POST /courses/{course}/learn/{page}/complete` — authorize `take`; run
    `CompletePage`; redirect to the next page's learn URL, or to the course
    completion state when finished.

- **`CourseCertificateController`**
  - `GET /courses/{course}/certificate` — authorize `viewCertificate`; render
    `Courses/Certificate`.

Route-model binding is scoped so `{page}` must belong to `{course}`.

### Frontend (Inertia + Vue 3, reusing existing components)

- **`resources/js/Pages/Courses/Learn.vue`** — the player:
  - A sidebar listing published pages in order, each with a completed (check)
    or locked icon and the current page highlighted; locked pages are not
    clickable. A progress bar / `x of y` indicator sits above the list.
  - A main panel rendering the current page's HTML content (`v-html`, matching
    `Pages/Show.vue`).
  - A **"Mark complete & continue"** button that POSTs to the complete route
    (Inertia `<Form>` or `router.post`) and lands on the next incomplete page.
  - When `is_complete`, the panel shows a "Course complete" state with a link
    to the certificate.

- **`resources/js/Pages/Courses/Certificate.vue`** — print-friendly layout:
  student full name, course title and code, completion date, and a print
  button.

- **Entry point** — on `resources/js/Pages/Courses/Index.vue`, enrolled
  students see a **"Take / Continue"** action and a small progress indicator on
  each Published course they are enrolled in. This keeps `Show.vue` purely
  management. (Reconsider a dedicated "My Courses" view later if needed.)

## Testing (Pest feature tests, using factories)

- **`CompletePage` / learn controller**
  - Enrolled student completes a page → `user_progress` row created.
  - Completing the final published page sets the pivot `completed_at`.
  - Completing a locked page (earlier page incomplete) → 403.
  - Non-enrolled user → 403; non-Published course → 403.
  - Completing an already-completed page is idempotent (no duplicate row, no
    error) and still resolves the next incomplete page.
  - Draft/Archived pages are not completable and do not appear in the sequence.

- **`LoadCoursePlayer`**
  - Correct `is_completed` / `is_locked` flags and `percent`.
  - Current-page resolution: first incomplete by default; requested page when
    unlocked.
  - Draft/Archived pages excluded from `pages` and from `total_count`.

- **`CoursePolicy`**
  - `take` truth table: enrolled + Published = allow; instructor/admin,
    non-enrolled, or non-Published = deny.
  - `viewCertificate`: denied until `completed_at` set, allowed after.

- **`CourseCertificateController`**
  - Forbidden until complete; renders once complete.

## Out of scope

Quizzes/assessments, time-on-page tracking, per-page notes/bookmarks,
instructor-facing progress dashboards, and resuming via a stored "current page"
pointer (resume is derived as the first incomplete page).

## Edge cases & notes

- A Published course with **zero** published pages: `total_count = 0`,
  `is_complete = false`, nothing to complete; the player shows an empty state.
- If an instructor publishes additional pages after a student finished, the
  derived `percent` drops below 100% and the course is no longer "complete";
  `completed_at` already stamped is retained (the certificate reflects the
  original completion). This is acceptable under Approach A and called out so it
  is a deliberate choice, not a surprise.
- Soft-deleted `user_progress` rows are not expected in normal flow; the unique
  index targets active rows via `firstOrCreate` on the standard query.
