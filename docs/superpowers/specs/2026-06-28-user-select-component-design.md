# UserSelect Component — Design

**Date:** 2026-06-28
**Status:** Approved

## Problem

The Instructor picker in `resources/js/Pages/Courses/Show.vue` uses a native HTML
`<select>`. Native `<option>` elements only support plain text, so the dropdown
cannot show user avatars. As the app grows it needs to attach different *types* of
users (instructors, students, …) to different *things* (courses, …), each ideally
showing the user's avatar in the picker. We want one reusable picker component
instead of duplicating native-select markup per page.

## Solution Overview

Create `resources/js/Components/UserSelect.vue` — a reusable, avatar-enabled user
picker built on the existing Shadcn `ui/select/` primitives (reka-ui), which (unlike
a native select) can render arbitrary content per option. The component is a *picker
only*: it emits the selected user id and the parent owns the surrounding form, the
data, and any "Add" action.

The first consumer is `Courses/Show.vue`, where the native instructor `<select>` is
replaced by `<UserSelect>`.

## Component API

`resources/js/Components/UserSelect.vue`

**Props**
- `users: Array` (required) — candidate users. Each item: `{ id, first_name, last_name, avatar? }`.
- `modelValue: [Number, String, null]` (default `null`) — selected user id; enables `v-model`.
- `placeholder: String` (default `'Select a user…'`) — shown in the trigger when nothing is selected.
- `variant: String` (default `'primary'`, validator: `primary | accent | darker`) — forwarded to `Avatar`.

**Emits**
- `update:modelValue` — the selected user's `id` (same type as supplied in `users`).

## Rendering

- Built from `Select`, `SelectTrigger`, `SelectContent`, `SelectItem` in
  `resources/js/Components/ui/select/`.
- **Each item:** `<Avatar :user="user" size="sm" :variant="variant" :zoomable="false" />`
  followed by the user's full name (`first_name last_name`). No email (per design choice).
- **Trigger (collapsed):** when a user is selected, show that user's avatar + full
  name; otherwise show the `placeholder` text. A `selectedUser` computed derives this
  from `users` and `modelValue`.

## Avatar change (targeted)

`Avatar.vue` currently always wraps the thumbnail in a click-to-zoom dialog when an
avatar image exists. Inside a select item this would hijack item selection. Add a
`zoomable: Boolean` prop (default `true`) to `Avatar.vue`. When `false`, render a
plain `<img>` thumbnail (with the existing initials fallback) and no dialog. This is
backward compatible — all existing usages omit the prop and keep zoom behavior.

## Data Flow (unchanged contract)

Parent (`Courses/Show.vue`) continues to own everything except the picker UI:

- `v-model="selected_instructor_id"` ↔ `UserSelect` `modelValue` / `update:modelValue`.
- `:users="assignable_instructors"`, `:placeholder="'Select an instructor…'"`.
- The existing `Button` + `addInstructor()` and the assigned-instructor list are
  untouched.

Because the emitted value is still the user id and the POST payload
(`{ user_id }`) is unchanged, the backend contract and existing instructor feature
tests remain valid.

## Out of Scope (YAGNI)

- No "Add" button, no assigned-user list, no remove logic inside the component.
- No multi-select.
- No search/filtering inside the dropdown (can be added later if lists grow large).

## Testing / Verification

- No JavaScript test harness exists in this project (tests are Pest/PHP). The
  existing PHP feature tests covering "add instructor" stay valid since the request
  payload is unchanged.
- Verify the UI by running `npm run build` and a quick browser check of the course
  page: the instructor dropdown shows avatars + names, selecting one enables Add,
  and adding still works.

## Files

- **New:** `resources/js/Components/UserSelect.vue`
- **Modified:** `resources/js/Components/Avatar.vue` (add `zoomable` prop)
- **Modified:** `resources/js/Pages/Courses/Show.vue` (use `UserSelect`)
