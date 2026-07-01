# Modal Enrollment & Hybrid Roster Filtering

**Date:** 2026-07-01

## Summary

Move the "add instructors / students / group-members" flows off the Course and
Group `Show.vue` pages and into **centered modal dialogs** that support
**multi-select** (pick several people, enrol them in one request). Add a
per-list **roster filter** on the Show pages that filters the already-enrolled
roster with a mix of front-end and back-end code so the queries stay fast and
the UI stays responsive.

The backend for the existing single-add flow (typeahead endpoints, validation,
policies, transactional bulk group-enrol) already exists; this work reshapes the
UI, bounds the initial roster payload, and adds bulk-store + roster-search
endpoints.

## Goals

- Adding instructors/students to a course (and members to a group) happens in a
  modal, not inline on the page.
- Users can select and add **multiple** people in a single action.
- Each roster card (Instructors, Students, Members) has a search box that
  filters the enrolled roster responsively without shipping thousands of rows to
  the browser or issuing a backend query on every keystroke of a small list.
- The course "enrol a whole group's members" flow moves into its **own**
  dedicated dialog, separate from the add-people dialog.

## Non-Goals

- No change to authorization rules (existing `CoursePolicy::manageInstructors`,
  `manageStudents`, `GroupPolicy::manageMembers` are reused unchanged).
- No new enrollment concepts — instructor/student remains the `is_instructor`
  pivot boolean; leader remains `is_leader`.
- No unrelated refactoring of the Show pages beyond swapping the roster/add
  blocks for the new components.

## Current State (for reference)

- `app/Actions/Courses/LoadCourseDetails.php` eager-loads the **full**
  `instructors` and `students` relations into props, plus `*_count` counts.
- `resources/js/Pages/Courses/Show.vue` renders each roster in a
  `ScrollableList` and has inline `UserSearchSelect` + Add-button rows, plus a
  `GroupSearchSelect` row for bulk group enrol.
- `resources/js/Pages/Groups/Show.vue` renders members inline with a per-member
  leader toggle + remove, and an inline `UserSearchSelect` + "add as leader"
  checkbox.
- Assignable typeahead endpoints (`*.assignable`), form-request validation,
  policies, and the transactional `EnrollGroupMembers` action already exist.
- `UserSearchSelect.vue` and `GroupSearchSelect.vue` each duplicate a
  `fetch()` + 250ms `setTimeout` debounce against a `searchUrl`.

## Architecture & Data Flow

### Bounded initial roster load

`LoadCourseDetails` (and a matching adjustment for the Group show path) load only
the **first page** of each roster — the first ~25 rows ordered by
`first_name`/`last_name` — instead of the entire relation. The existing
`*_count` counts are kept so the UI knows whether more rows exist beyond what was
loaded. This is what makes the hybrid filter honest: without it, client-side
filtering would only ever see the first slice of a large roster.

### Roster search/pagination endpoints (new)

New JSON endpoints return additional/filtered roster rows on demand:

- `GET courses/{course}/instructors` → `courses.instructors.index`
- `GET courses/{course}/students`    → `courses.students.index`
- `GET groups/{group}/members`       → `groups.members.index`

Each accepts `?search=` and `?page=`, is authorized by the same policy ability as
the corresponding management action, filters the **enrolled** roster by
`first_name` / `last_name` / `email`, orders by name, paginates (page size ~25),
and returns the same user shape used elsewhere (`id, first_name, last_name,
email` + appended `avatar`).

### Bulk store

The `store` endpoints accept an **array** of ids and enrol them atomically:

- `POST courses/{course}/instructors` — body `user_ids: number[]`
- `POST courses/{course}/students`    — body `user_ids: number[]`
- `POST groups/{group}/members`       — body `user_ids: number[]`; members enrol
  as non-leaders (leader is toggled per-row on the roster afterwards)
- `POST courses/{course}/students/group` — body `group_ids: number[]`

Each is wrapped in a DB transaction, skips users already enrolled, and restores
soft-deleted pivot rows where applicable (mirroring the existing
`EnrollGroupMembers` behaviour). If any supplied id fails validation
(wrong role / not assignable), the request is rejected and **nothing** is
enrolled.

## Frontend Components

Each component has a single responsibility, a defined prop interface, and can be
understood/tested in isolation.

### `resources/js/components/ui/dialog/` (new primitive)

A reka-ui centered modal primitive following the existing `ui/alert-dialog` and
`ui/sheet` conventions: `Dialog`, `DialogTrigger`, `DialogContent`,
`DialogHeader`, `DialogFooter`, `DialogTitle`, `DialogDescription`,
`DialogClose`, plus the `index.js` barrel. Generic and reusable across the app.

### `resources/js/composables/useDebouncedSearch.js` (new)

Lifts the duplicated `fetch()` + 250ms debounce + JSON parsing out of
`UserSearchSelect`/`GroupSearchSelect` into one composable returning
`{ results, loading, search(term), reset() }`. Consumed by the search-selects,
the assign dialogs, and `RosterList`. `UserSearchSelect`/`GroupSearchSelect` are
refactored to use it (behaviour unchanged).

### `resources/js/components/AssignDialog.vue` (new)

Reusable "add people" modal built on `ui/dialog`.

- **Props:** `title`, `description`, `searchUrl` (assignable typeahead),
  `storeUrl`, `variant` (`primary`/`accent`), optional `triggerLabel`.
- **Behaviour:** debounced backend typeahead (via `useDebouncedSearch`) →
  checkbox list of results (avatar + name + email) → tracks a set of selected
  ids → "Add (n)" button posts `{ user_ids: [...] }` to `storeUrl` via
  `router.post` with `preserveScroll`, closes on success, resets selection.
- Used for **both** course instructors and course students (different
  `searchUrl`/`storeUrl`/`variant`) **and** group members.
- Group-member note: the "add as leader" affordance is preserved as a per-row or
  footer control inside this dialog (members enrol as non-leaders by default,
  matching current behaviour; leader can still be toggled afterwards on the
  roster row as today).

### `resources/js/components/AssignGroupsDialog.vue` (new, separate)

Dedicated dialog for enrolling a whole group's members into a course — kept
**separate** from `AssignDialog` per the requirement.

- **Props:** `title`, `searchUrl` (assignable-groups typeahead), `storeUrl`.
- **Behaviour:** debounced group typeahead (name + description) → checkbox list
  → posts `{ group_ids: [...] }` to `courses.students.storeGroup`. Shares the
  `ui/dialog` shell and `useDebouncedSearch` composable with `AssignDialog` but
  renders group items and posts a different payload.

### `resources/js/components/RosterList.vue` (new)

Replaces the inline `ScrollableList` roster blocks.

- **Props:** initial `items` (first roster page), `count` (total enrolled),
  `searchUrl` (roster index endpoint), `variant`; a `#actions` slot per row for
  the remove button / leader toggle so Course and Group pages keep their
  page-specific row controls.
- **Behaviour (hybrid filter):**
  - A search input filters the currently-loaded rows **client-side**
    instantly (name/email match).
  - If a search term is present **and** (`count > items.length` — i.e. more rows
    exist server-side — or the client filter yields no local match), it
    debounce-fetches the roster `searchUrl` and renders server results instead.
  - Clearing the term restores the initial client-side list.
  - "Load more" pagination pulls the next page from `searchUrl` when the roster
    exceeds the loaded set.
- Emits nothing about mutation; removal/leader-toggle stay in the parent via the
  slot, so `RosterList` is purely presentation + filtering.

### Page changes

- `Courses/Show.vue`: Instructors card → `<RosterList>` + an **Add instructors**
  button opening `<AssignDialog>`. Students card → `<RosterList>` + an **Add
  students** button (`<AssignDialog>`) and a separate **Add a group** button
  (`<AssignGroupsDialog>`).
- `Groups/Show.vue`: Members list → `<RosterList>` (leader toggle + remove in the
  `#actions` slot) + an **Add members** button opening `<AssignDialog>`.

## Backend Detail

### Actions (`app/Actions/{Courses,Groups}/`)

- **Roster search:** `SearchCourseInstructors`, `SearchCourseStudents`,
  `SearchGroupMembers` — accept the model + `?search`/`?page`, query the enrolled
  relation with the name/email filter, order by name, paginate (~25), eager-load
  `media` for avatars.
- **Bulk assign:** the assign actions accept an **array** of ids, run in a
  transaction, skip already-enrolled, restore soft-deleted pivots. Reuse the
  `EnrollGroupMembers` pattern. Single-id call sites (if any remain) pass a
  one-element array.

### Controllers

- `CourseInstructorController`, `CourseStudentController`, `GroupMemberController`
  gain an `index(...)` method (roster JSON, `authorize` via the management
  ability) delegating to the search action.
- `store` methods delegate validated `user_ids` to the bulk assign action.
- `CourseStudentController::storeGroup` accepts `group_ids[]`.

### Form Requests (`app/Http/Requests/`)

- Store requests: `user_ids: required|array|min:1`; `user_ids.*` reuses the
  existing per-user closures (correct role, not already enrolled). Group-students
  request: `group_ids: required|array|min:1`, `group_ids.*` exists on `groups`.
- Roster `index` requests (or inline controller validation): `search: nullable|string`,
  `page: nullable|integer|min:1`.
- Authorization stays in the request/policy as per existing convention.

### Enums

No magic strings — role filtering continues through `App\Enums\UserRole`
(`UserRole::values(...)`), never literal role strings.

## Error Handling

- Bulk store is atomic: a single invalid id fails validation and enrols nobody
  (`422` with field errors keyed to `user_ids.*` / `group_ids.*`).
- Roster/typeahead endpoints require the management policy ability (`403`
  otherwise) and are capped/paginated to bound query cost.
- Client fetches degrade gracefully: a failed roster fetch leaves the last good
  client-filtered list and surfaces no destructive state.

## Testing (Pest feature tests)

- **Bulk store** — enrols multiple valid users; a mixed valid/invalid batch is
  rejected atomically (nobody enrolled); duplicates are skipped; soft-deleted
  pivots are restored; wrong-role ids rejected; unauthorized user gets `403`.
- **Group bulk enrol** — `group_ids[]` enrols each group's student members,
  skipping existing/non-students (existing `EnrollGroupMembers` coverage
  extended for the array shape).
- **Roster index endpoints** — search matches first/last/email; results limited
  to the enrolled roster; pagination returns subsequent pages; only appropriate
  fields returned; authorization enforced.
- **Bounded initial load** — Course/Group show props carry at most the first
  roster page plus the full counts.

Use model factories and their existing states; assert via Inertia/JSON response
helpers per project convention.

## Build Sequence

1. `ui/dialog` primitive + `useDebouncedSearch` composable (refactor the two
   existing search-selects onto it — behaviour unchanged, tests green).
2. Backend: bulk-store (arrays) + form-request updates + tests.
3. Backend: roster `index` endpoints + search actions + tests; bound
   `LoadCourseDetails` (and group show) initial load + tests.
4. Frontend: `AssignDialog`, `AssignGroupsDialog`, `RosterList`.
5. Wire `Courses/Show.vue` and `Groups/Show.vue` to the new components; remove
   the inline blocks.
6. Full test pass + `pint` + `npm run build` sanity.
