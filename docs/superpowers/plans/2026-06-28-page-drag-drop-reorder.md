# Drag-and-Drop Page Reordering Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let course managers reorder a course's pages by dragging them on the course Show page, persisting via the existing reorder endpoint, while keeping the up/down arrow buttons as an accessible fallback.

**Architecture:** Frontend-only. Add the `vuedraggable@next` library and replace the static pages `v-for` in `resources/js/Pages/Courses/Show.vue` with a `<draggable>` list bound to a local reactive copy of `course.pages`. A drag handle starts the drag; on drop, the existing `persistPageOrder` helper PUTs the ordered IDs to the already-built `pages.reorder` endpoint. No backend changes.

**Tech Stack:** Vue 3.5 (`<script setup>`), Inertia v3, vuedraggable@next (SortableJS), Tailwind v4, lucide-vue-next, Vite.

## Global Constraints

- Frontend-only change. Do NOT modify any backend file, route, controller, action, or migration.
- New dependency limited to exactly `vuedraggable@next` (Vue 3 / SortableJS wrapper) — approved.
- Drag is enabled only for managers — gate on the existing `canManage` computed (`props.can.update`).
- Keep the existing up/down arrow buttons (`movePage`) working as an accessible fallback.
- A drag handle (lucide `GripVertical`) initiates the drag; existing row controls (page link, Edit, Delete) must stay clickable.
- Naming: variables `snake_case`, methods/functions `camelCase`; full descriptive names.
- Vue components must have a single root element.
- Verification is `npm run build` (no JS test harness exists) plus the existing backend reorder tests in `tests/Feature/PageControllerTest.php` (must still pass — they are unaffected).

---

## File Structure

- Modify `package.json` (+ lockfile) — add `vuedraggable@next` (Task 1).
- Modify `resources/js/Pages/Courses/Show.vue` — script (`watch`, local `ordered_pages`, `GripVertical` + `draggable` imports, `movePage`/`onDragEnd`) and the Pages-section template (Task 2).

No backend files change. The reorder route (`PUT courses/{course}/pages/reorder`), `PageController::reorder`, `ReorderPages` action, and `ReorderPagesRequest` already exist and are tested.

---

### Task 1: Add the `vuedraggable` dependency

**Files:**
- Modify: `package.json` (+ `package-lock.json`)

**Interfaces:**
- Produces: the `vuedraggable` package importable as `import draggable from 'vuedraggable';` (default export is the draggable component). Consumed by Task 2.

- [ ] **Step 1: Install the dependency**

Run:
```bash
npm install vuedraggable@next
```
Expected: completes successfully; `package.json` `dependencies` now lists `"vuedraggable"` at a `^4.x` version (the `@next` tag resolves to the Vue 3-compatible 4.x release), and `package-lock.json` is updated (SortableJS is pulled in transitively).

- [ ] **Step 2: Verify it resolves and the app still builds**

Run:
```bash
node -e "console.log('vuedraggable', require('vuedraggable/package.json').version)"
npm run build
```
Expected: prints a `vuedraggable 4.x.x` version line; `npm run build` finishes with `✓ built` and no errors.

- [ ] **Step 3: Commit**

```bash
git add package.json package-lock.json
git commit -m "build: add vuedraggable for page drag-and-drop reordering"
```

---

### Task 2: Drag-and-drop the pages list in `Show.vue`

**Files:**
- Modify: `resources/js/Pages/Courses/Show.vue`

**Interfaces:**
- Consumes: `vuedraggable` default export (Task 1); the existing `persistPageOrder(ordered_pages)` helper (lines 128–134) which PUTs `{ pages: ordered_pages.map(p => p.id) }` to `route('pages.reorder', props.course.id)` with `preserveScroll: true`; the existing `canManage` computed (`props.can.update`).
- Produces: nothing consumed by later tasks (final task).

This task has no automated unit test (no JS test harness). It is verified by `npm run build` and by confirming the existing backend reorder tests still pass. Make the four script edits, then the template edit, then verify.

- [ ] **Step 1: Add `watch` to the vue import**

In `resources/js/Pages/Courses/Show.vue`, change line 2 from:

```js
import { computed, ref } from 'vue';
```
to:
```js
import { computed, ref, watch } from 'vue';
```

- [ ] **Step 2: Add `GripVertical` to the lucide import and import `draggable`**

Change the lucide import block (lines 4–7) from:

```js
import {
    ArrowLeft, Pencil, Trash2, Tag as TagIcon, Users, User, FileText,
    Plus, ChevronUp, ChevronDown, Info, AlignLeft, X, UserPlus,
} from 'lucide-vue-next';
```
to (adds `GripVertical`):
```js
import {
    ArrowLeft, Pencil, Trash2, Tag as TagIcon, Users, User, FileText,
    Plus, ChevronUp, ChevronDown, Info, AlignLeft, X, UserPlus, GripVertical,
} from 'lucide-vue-next';
```

Then add the draggable import immediately after the `AppLayout` import (after line 14):

```js
import draggable from 'vuedraggable';
```

- [ ] **Step 3: Add the local `ordered_pages` state + prop sync**

In the `<script setup>`, immediately after the `deletePage` function and before `persistPageOrder` (i.e. between line 126 and line 128), insert:

```js
const ordered_pages = ref([...(props.course.pages ?? [])]);

watch(
    () => props.course.pages,
    (pages) => {
        ordered_pages.value = [...(pages ?? [])];
    },
);

const onDragEnd = () => {
    persistPageOrder(ordered_pages.value);
};
```

- [ ] **Step 4: Update `movePage` to drive the shared local list**

Replace the existing `movePage` function (lines 136–145) with:

```js
const movePage = (index, direction) => {
    const target_index = index + direction;
    if (target_index < 0 || target_index >= ordered_pages.value.length) {
        return;
    }
    const reordered = [...ordered_pages.value];
    [reordered[index], reordered[target_index]] =
        [reordered[target_index], reordered[index]];
    ordered_pages.value = reordered;
    persistPageOrder(reordered);
};
```

- [ ] **Step 5: Replace the pages list markup with a `<draggable>` list**

In the template, replace the entire pages list block — the `<div v-if="course.pages && course.pages.length > 0" class="space-y-3">` element and its `v-for` child (lines 386–445) — with the following. Leave the `v-else` empty-state block (lines 446–453) exactly as it is; it now pairs with the `<draggable>`'s `v-if`.

```vue
          <draggable
              v-if="ordered_pages.length > 0"
              v-model="ordered_pages"
              item-key="id"
              handle=".page-drag-handle"
              :disabled="!canManage"
              class="space-y-3"
              @end="onDragEnd"
          >
            <template #item="{ element: page, index }">
              <div
                  class="flex items-center justify-between p-4 bg-darker-50 rounded-lg hover:bg-darker-100 transition-colors"
              >
                <div class="flex items-center gap-3">
                  <button
                      v-if="canManage"
                      type="button"
                      class="page-drag-handle cursor-grab active:cursor-grabbing text-darker-400 hover:text-primary-600"
                      aria-label="Drag to reorder page"
                  >
                    <GripVertical class="w-4 h-4" />
                  </button>
                  <div v-if="canManage" class="flex flex-col">
                    <button
                        type="button"
                        class="text-darker-400 hover:text-primary-600 disabled:opacity-30"
                        :disabled="index === 0"
                        aria-label="Move page up"
                        @click="movePage(index, -1)"
                    >
                      <ChevronUp class="w-4 h-4" />
                    </button>
                    <button
                        type="button"
                        class="text-darker-400 hover:text-primary-600 disabled:opacity-30"
                        :disabled="index === ordered_pages.length - 1"
                        aria-label="Move page down"
                        @click="movePage(index, 1)"
                    >
                      <ChevronDown class="w-4 h-4" />
                    </button>
                  </div>
                  <span class="flex items-center justify-center w-8 h-8 rounded-full bg-primary-200 text-primary-700 font-semibold text-sm">
                    {{ index + 1 }}
                  </span>
                  <div>
                    <button
                        type="button"
                        class="font-semibold text-darker-900 hover:text-primary-600 text-left"
                        @click="viewPage(page)"
                    >
                      {{ page.title }}
                    </button>
                    <p class="text-sm text-darker-600">Status: {{ page.status }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <template v-if="canManage">
                    <Button variant="ghost" size="icon-sm" aria-label="Edit page" @click="editPage(page)">
                      <Pencil class="w-4 h-4" />
                    </Button>
                    <ConfirmAction
                        title="Delete page?"
                        :description="`Are you sure you want to delete &quot;${page.title}&quot;?`"
                        confirm-label="Delete"
                        @confirm="deletePage(page)"
                    >
                      <Button variant="ghost" size="icon-sm" class="text-destructive hover:bg-destructive/10" aria-label="Delete page">
                        <Trash2 class="w-4 h-4" />
                      </Button>
                    </ConfirmAction>
                  </template>
                </div>
              </div>
            </template>
          </draggable>
```

Notes for the implementer:
- The numbered badge now shows `{{ index + 1 }}` (list position) instead of `{{ page.order }}`, so it stays correct during the optimistic state before the server refresh.
- The up/down buttons now bound their disabled state on `ordered_pages.length`, not `course.pages.length`.
- Do not change the `v-else` empty-state block below it.

- [ ] **Step 6: Build to verify it compiles**

Run:
```bash
npm run build
```
Expected: `✓ built` with no errors; a `Show` chunk is emitted.

- [ ] **Step 7: Confirm the backend reorder tests are unaffected**

Run:
```bash
php artisan test --compact tests/Feature/PageControllerTest.php
```
Expected: all tests pass, including "reorders pages within a course" and "rejects reordering with a page from another course".

- [ ] **Step 8: Commit**

```bash
git add resources/js/Pages/Courses/Show.vue
git commit -m "feat: drag-and-drop reordering for course pages"
```

---

## Self-Review

**Spec coverage:**
- Add `vuedraggable@next` dependency → Task 1. ✓
- Drag handle (`GripVertical`) initiates drag; row controls stay clickable (`handle=".page-drag-handle"`) → Task 2 Step 5. ✓
- Local `ordered_pages` copy + `watch` re-sync from `course.pages` prop → Task 2 Step 3. ✓
- `<draggable>` v-model, `item-key="id"`, `disabled` for non-managers → Task 2 Step 5. ✓
- Persist on drop via existing `persistPageOrder` (`@end` → `onDragEnd`) → Task 2 Steps 3, 5. ✓
- Up/down buttons kept and share `ordered_pages` (`movePage` updated) → Task 2 Step 4. ✓
- Position shown as 1-based index (not stale `order`) → Task 2 Step 5. ✓
- Manager-only gating via `canManage` → Task 2 Step 5 (`:disabled="!canManage"`, `v-if="canManage"` on handle/buttons). ✓
- No backend changes; existing reorder tests still pass → Task 2 Step 7. ✓
- Empty state preserved → Task 2 Step 5 note (leave `v-else` block). ✓

**Placeholder scan:** No TBD/TODO; every step has concrete code or an exact command with expected output.

**Type/name consistency:** `ordered_pages` (ref), `onDragEnd`, `movePage`, `persistPageOrder`, `canManage`, the `.page-drag-handle` selector, and the `#item="{ element: page, index }"` slot binding are used consistently across Steps 3–5. The draggable default import name `draggable` matches the `<draggable>` tag.
