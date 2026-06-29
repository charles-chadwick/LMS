# Drag-and-Drop Page Reordering — Design

**Date:** 2026-06-28
**Status:** Approved (design); pending implementation plan

## Summary

Let instructors/admins reorder a course's pages by dragging them in the list on
the course Show page, instead of only clicking up/down arrows. The drag updates
the order optimistically and persists via the existing reorder endpoint. The
up/down arrow buttons are kept as a keyboard-accessible fallback.

This is a **frontend-only** feature. The backend already supports reordering:
`PUT courses/{course}/pages/reorder` (`PageController::reorder` → `ReorderPages`
action) accepts an ordered array of page IDs, authorized by `update` on the
course and validated by `ReorderPagesRequest` (each id must belong to the
course). Two feature tests already cover it
(`tests/Feature/PageControllerTest.php`: "reorders pages within a course",
"rejects reordering with a page from another course").

## Product decisions (from brainstorming)

- Implementation uses the **`vuedraggable@next`** library (Vue 3 wrapper around
  SortableJS) — approved as a new frontend dependency.
- A **drag handle** (grip icon) initiates the drag, so existing row controls
  (View/Edit/Delete, page link) remain clickable.
- The **up/down arrow buttons are kept** alongside drag for keyboard/
  accessibility users.
- Dragging is enabled only for users who can manage the course (the same
  `can.update` condition that currently gates the up/down buttons).

## Scope

- In scope: drag-to-reorder on the pages list of
  `resources/js/Pages/Courses/Show.vue`; persisting via the existing endpoint;
  adding the `vuedraggable` dependency.
- Out of scope: any backend change; reordering anywhere other than the course
  Show page; drag-and-drop for instructors/students lists; nested/grouped pages.

## Architecture

### Dependency

Add `vuedraggable@next` (Vue 3 compatible release) to `package.json`
dependencies and install it. No other dependency changes.

### Frontend — `resources/js/Pages/Courses/Show.vue` (Pages section only)

- Introduce a local reactive list, `ordered_pages`, initialized from the
  `course.pages` prop. A `watch` on `course.pages` re-syncs `ordered_pages`
  whenever the prop changes (after the server redirect that follows a reorder,
  or on navigation), so optimistic state and server state converge.
- Replace the static `v-for` over `course.pages` in the pages list with a
  `<draggable>` (vuedraggable) component bound with `v-model="ordered_pages"`,
  keyed by `page.id`, configured with:
  - `handle` set to a CSS selector for the grip handle element, so only the
    handle starts a drag;
  - `item-key="id"`;
  - `disabled` when the user cannot manage the course (`!can.update`).
- Add a grip handle (lucide `GripVertical`) as the first element of each row,
  visible only to managers. Keep the existing View/Edit/Delete controls and the
  up/down arrow buttons.
- Display each row's position as its **1-based list index** (`index + 1`)
  rather than `page.order`, so the number stays correct during the optimistic
  pre-refresh state.
- On drag end (vuedraggable `@end`), call the existing `persistPageOrder`
  helper with the current `ordered_pages`. `persistPageOrder` already PUTs
  `{ pages: ordered_pages.map(p => p.id) }` to `route('pages.reorder', course.id)`
  with `preserveScroll: true`.
- The existing `movePage(index, direction)` helper continues to drive the
  up/down buttons; update it to operate on `ordered_pages` (so both paths share
  one source of truth) and call `persistPageOrder`.

### Backend

No changes. Existing route, controller, action, form request, and tests stand.

## Data flow

1. Manager drags a page by its handle; vuedraggable reorders `ordered_pages`
   locally (optimistic).
2. `@end` → `persistPageOrder(ordered_pages)` → `router.put` to `pages.reorder`
   with the ordered ID array, `preserveScroll`.
3. `ReorderPagesRequest` validates; `ReorderPages` action rewrites each page's
   `order`; controller redirects to `courses.show` with a success flash.
4. Inertia refreshes props; the `watch` re-syncs `ordered_pages` from the new
   `course.pages`.

## Error handling

- Validation failure (e.g. a tampered ID not belonging to the course) returns
  the standard Inertia validation error response; the list re-syncs from props
  on the next successful load. No custom handling beyond Laravel/Inertia
  defaults.
- Authorization is enforced server-side (`update` on the course) regardless of
  the client disabling drag; a non-manager cannot persist a reorder.

## Testing

- No JavaScript test harness exists in the project, so the drag interaction is
  verified by `npm run build` (compiles) plus a manual drag check in the
  browser.
- The backend reorder behavior remains covered by the two existing
  `PageControllerTest` tests; no backend test changes are needed.

## Edge cases & notes

- Non-managers: drag disabled and no handle shown; they see the read-only list.
- A course with zero or one page: the draggable list renders but there is
  nothing to reorder; no special handling required.
- Optimistic order vs. server `order`: the 1-based index display avoids showing
  a stale `page.order` between drop and refresh.
