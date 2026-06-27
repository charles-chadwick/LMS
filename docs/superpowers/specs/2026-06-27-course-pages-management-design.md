# Course Pages Management — Design

**Date:** 2026-06-27

## Goal

Provide a way to add and manage pages within courses. The data layer (`Page`
model, `pages` migration, `PageFactory`) already exists and pages are displayed
read-only on the course screen. This adds the full management layer — create,
edit, soft-delete, and reorder — mirroring the existing Course CRUD pattern
(thin controller → single-purpose Actions → FormRequests → Inertia/Vue forms).

## Decisions

- **Scope:** Full CRUD + reorder.
- **Content editing:** WYSIWYG via PrimeVue `Editor` (Quill 2.0.3 already a
  dependency — no new packages).
- **Routing:** Top-level page routes with a course picker in the form.
- **Reorder UI:** Up/down buttons (dependency-free, reuses existing row layout).
- **Restore / force-delete:** Omitted for pages (YAGNI — no restore UI exists).

## Backend

### Routes (`routes/web.php`, new `pages` group)

| Method | URI | Name | Purpose |
|---|---|---|---|
| GET | `/pages/create` | `pages.create` | Form with course picker |
| POST | `/pages` | `pages.store` | Create |
| GET | `/pages/{page}` | `pages.show` | View rendered page |
| GET | `/pages/{page}/edit` | `pages.edit` | Edit form |
| PUT | `/pages/{page}` | `pages.update` | Update |
| DELETE | `/pages/{page}` | `pages.destroy` | Soft delete |
| PUT | `/courses/{course}/pages/reorder` | `pages.reorder` | Persist new order |

### PageController

Thin controller mirroring `CourseController`: resolves the relevant Action via
method injection, passes `$request->validated()` to `execute()`, returns
`Inertia::render()` or `redirect()` with a flash message.

### Actions (`app/Actions/Pages/`)

- `CreatePage` — auto-assigns `order` = (max order for that course) + 1 so new
  pages append to the end.
- `UpdatePage` — updates from validated attributes.
- `DeletePage` — soft delete; returns the page title for the flash message.
- `ReorderPages` — given a course and an ordered array of page IDs, rewrites
  each page's `order` to match the array position.
- `LoadPageDetails` — eager-loads relationships/course needed by the show view.

### FormRequests

- `StorePageRequest` — `course_id` required/`exists:courses`; `status` required
  string; `title` required string max:255; `content` required string. `order`
  is not user-supplied (auto-assigned).
- `UpdatePageRequest` — same rules as store.
- `ReorderPagesRequest` — `pages` required array; each element an integer page
  ID that belongs to the route's course.

`created_by_id` / `updated_by_id` are set automatically by the `Base` model
boot hooks.

## Frontend (Inertia + Vue, PrimeVue — mirrors `resources/js/Pages/Courses/`)

- **`Pages/Form.vue`** — create/edit. Fields: course `Select` (preselected from
  `?course_id=` when launched from a course), status `Select`, title
  `InputText`, content `Editor` (WYSIWYG). Same `useForm` + flash pattern as
  `Courses/Form.vue`.
- **`Pages/Show.vue`** — renders page title + HTML content read-only.
- **`Courses/Show.vue`** — the existing Pages card becomes interactive: an
  "Add Page" button (→ `/pages/create?course_id=`), and each row gains
  Edit / Delete actions plus up/down reorder controls that call `pages.reorder`.

## Factory & Seeder

- **`PageFactory`** (exists) — fix `status` to store the enum `.value` string
  (currently stores the enum case object); add a `forCourse()` state helper.
- **`PageSeeder`** (new) — gives each existing course several sequentially
  ordered pages; creates a few courses first if none exist. Wired into
  `DatabaseSeeder`.

## Tests (Pest feature tests)

- Store: happy path creates a page with appended order; validation failures
  (missing course_id, invalid course_id, missing title/content).
- Update: happy path; validation failures.
- Destroy: soft-deletes the page.
- Reorder: order persists in the given sequence; IDs from another course are
  rejected.
