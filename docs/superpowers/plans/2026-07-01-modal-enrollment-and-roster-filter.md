# Modal Enrollment & Hybrid Roster Filtering Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move add-instructor/student/member flows into centered multi-select modals, add a hybrid (client + backend) roster filter to the Course/Group show pages, and bound the initial roster payload.

**Architecture:** Add a reusable `ui/dialog` primitive and a `useDebouncedSearch` composable. New bulk-store Actions accept arrays of ids (atomic, skip-existing, restore soft-deleted). New paginated/searchable roster `index` endpoints back a `RosterList` component that filters loaded rows client-side and falls back to the server for larger rosters. `LoadCourseDetails`/`LoadGroupDetails` load only the first roster page plus counts.

**Tech Stack:** Laravel 13 (PHP 8.4), Inertia v3, Vue 3, reka-ui, Tailwind v4, Pest v4.

## Global Constraints

- Naming: variables `snake_case`; methods/functions `camelCase`; classes `TitleCase` (per global standards). Follow existing file conventions when they differ (e.g. Action `execute()`).
- No magic strings: use `App\Enums\UserRole` (`UserRole::values(...)`), never literal role strings.
- Keep controllers thin: business logic in single-purpose Action classes under `app/Actions/{Courses,Groups}/`, each with one public `execute()`.
- PHP: curly braces always; constructor property promotion; explicit return types and param type hints; PHPDoc over inline comments.
- Tests are Pest (`it()`/`expect()`), feature tests use `uses(LazilyRefreshDatabase::class)` and the `userWithRole(UserRole)` helper. Use factories.
- After any PHP change run `vendor/bin/pint --dirty --format agent`.
- Roster/typeahead page size: **25**.
- Bulk semantics: validation rejects wrong-role ids atomically (nothing enrolled); the Action skips already-enrolled and restores soft-deleted pivots.
- Frontend has no JS test runner — verify frontend with `npm run build` (no Vite manifest errors) plus Pest Inertia assertions on the show pages.

---

## File Structure

**Backend — create:**
- `app/Actions/Courses/AssignInstructors.php` — bulk attach instructors (atomic, skip/restore).
- `app/Actions/Courses/AssignStudents.php` — bulk attach students.
- `app/Actions/Groups/AssignMembers.php` — bulk attach group members.
- `app/Actions/Courses/EnrollGroups.php` — bulk-enroll several groups' members.
- `app/Actions/Courses/SearchCourseInstructors.php` — paginated/searchable instructor roster.
- `app/Actions/Courses/SearchCourseStudents.php` — paginated/searchable student roster.
- `app/Actions/Groups/SearchGroupMembers.php` — paginated/searchable member roster (with `is_leader` pivot).
- `app/Http/Requests/IndexCourseRosterRequest.php` — shared `search`/`page` validation for course roster endpoints.
- `app/Http/Requests/IndexGroupMembersRequest.php` — `search`/`page` validation for group members.

**Backend — modify:**
- `app/Http/Requests/StoreCourseInstructorRequest.php` — `user_id` → `user_ids[]`.
- `app/Http/Requests/StoreCourseStudentRequest.php` — `user_id` → `user_ids[]`.
- `app/Http/Requests/StoreGroupMemberRequest.php` — `user_id`+`is_leader` → `user_ids[]`.
- `app/Http/Requests/StoreCourseGroupStudentsRequest.php` — `group_id` → `group_ids[]`.
- `app/Http/Controllers/CourseInstructorController.php` — `store` bulk + new `index`.
- `app/Http/Controllers/CourseStudentController.php` — `store`/`storeGroup` bulk + new `index`.
- `app/Http/Controllers/GroupMemberController.php` — `store` bulk + new `index`.
- `app/Actions/Courses/LoadCourseDetails.php` — bound rosters to first 25.
- `app/Actions/Groups/LoadGroupDetails.php` — bound members to first 25.
- `routes/web.php` — add three roster `index` GET routes.

**Backend — modify existing tests (array shape):**
- `tests/Feature/CourseInstructorControllerTest.php`, `tests/Feature/CourseStudentControllerTest.php`, `tests/Feature/GroupControllerTest.php` (any post using `user_id`/`is_leader`/`group_id`).

**Frontend — create:**
- `resources/js/components/ui/dialog/` — `Dialog.vue`, `DialogTrigger.vue`, `DialogContent.vue`, `DialogHeader.vue`, `DialogFooter.vue`, `DialogTitle.vue`, `DialogDescription.vue`, `DialogClose.vue`, `index.js`.
- `resources/js/composables/useDebouncedSearch.js`.
- `resources/js/components/AssignDialog.vue`.
- `resources/js/components/AssignGroupsDialog.vue`.
- `resources/js/components/RosterList.vue`.

**Frontend — modify:**
- `resources/js/components/UserSearchSelect.vue`, `resources/js/components/GroupSearchSelect.vue` — use `useDebouncedSearch`.
- `resources/js/Pages/Courses/Show.vue`, `resources/js/Pages/Groups/Show.vue` — wire new components.

---

## Task 1: `ui/dialog` primitive + `useDebouncedSearch` composable

**Files:**
- Create: `resources/js/components/ui/dialog/{Dialog,DialogTrigger,DialogContent,DialogHeader,DialogFooter,DialogTitle,DialogDescription,DialogClose}.vue`, `resources/js/components/ui/dialog/index.js`
- Create: `resources/js/composables/useDebouncedSearch.js`
- Modify: `resources/js/components/UserSearchSelect.vue`, `resources/js/components/GroupSearchSelect.vue`

**Interfaces:**
- Produces: `Dialog` primitive set (reka-ui `DialogRoot`-based, centered). `useDebouncedSearch(searchUrl, { delay = 250 })` → `{ results, loading, search(term), reset() }` where `results` is a `ref([])` of parsed JSON, `search(term)` debounces a `fetch`.

- [ ] **Step 1: Create the dialog primitive files**

`resources/js/components/ui/dialog/Dialog.vue`:
```vue
<script setup>
import { DialogRoot, useForwardPropsEmits } from "reka-ui";

const props = defineProps({
  open: { type: Boolean, required: false },
  defaultOpen: { type: Boolean, required: false },
  modal: { type: Boolean, required: false },
});
const emits = defineEmits(["update:open"]);
const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
  <DialogRoot v-bind="forwarded">
    <slot />
  </DialogRoot>
</template>
```

`resources/js/components/ui/dialog/DialogTrigger.vue`:
```vue
<script setup>
import { DialogTrigger, useForwardProps } from "reka-ui";
const props = defineProps({ asChild: { type: Boolean, required: false }, as: { type: null, required: false } });
const forwarded = useForwardProps(props);
</script>

<template>
  <DialogTrigger v-bind="forwarded"><slot /></DialogTrigger>
</template>
```

`resources/js/components/ui/dialog/DialogClose.vue`:
```vue
<script setup>
import { DialogClose } from "reka-ui";
const props = defineProps({ asChild: { type: Boolean, required: false }, as: { type: null, required: false } });
</script>

<template>
  <DialogClose v-bind="props"><slot /></DialogClose>
</template>
```

`resources/js/components/ui/dialog/DialogContent.vue`:
```vue
<script setup>
import { reactiveOmit } from "@vueuse/core";
import { X } from "lucide-vue-next";
import {
  DialogClose, DialogContent, DialogOverlay, DialogPortal, useForwardPropsEmits,
} from "reka-ui";
import { cn } from "@/lib/utils";

defineOptions({ inheritAttrs: false });

const props = defineProps({
  class: { type: null, required: false },
  forceMount: { type: Boolean, required: false },
  disableOutsidePointerEvents: { type: Boolean, required: false },
  asChild: { type: Boolean, required: false },
  as: { type: null, required: false },
});
const emits = defineEmits([
  "escapeKeyDown", "pointerDownOutside", "focusOutside", "interactOutside", "openAutoFocus", "closeAutoFocus",
]);
const delegatedProps = reactiveOmit(props, "class");
const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
  <DialogPortal>
    <DialogOverlay
      class="fixed inset-0 z-50 bg-black/80 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0"
    />
    <DialogContent
      :class="cn(
        'fixed left-1/2 top-1/2 z-50 grid w-full max-w-lg -translate-x-1/2 -translate-y-1/2 gap-4 border bg-background p-6 shadow-lg duration-200 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 sm:rounded-lg',
        props.class,
      )"
      v-bind="{ ...forwarded, ...$attrs }"
    >
      <slot />
      <DialogClose
        class="absolute right-4 top-4 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none"
      >
        <X class="w-4 h-4 text-muted-foreground" />
      </DialogClose>
    </DialogContent>
  </DialogPortal>
</template>
```

`resources/js/components/ui/dialog/DialogHeader.vue`:
```vue
<script setup>
import { cn } from "@/lib/utils";
const props = defineProps({ class: { type: null, required: false } });
</script>

<template>
  <div :class="cn('flex flex-col gap-1.5 text-center sm:text-left', props.class)"><slot /></div>
</template>
```

`resources/js/components/ui/dialog/DialogFooter.vue`:
```vue
<script setup>
import { cn } from "@/lib/utils";
const props = defineProps({ class: { type: null, required: false } });
</script>

<template>
  <div :class="cn('flex flex-col-reverse gap-2 sm:flex-row sm:justify-end', props.class)"><slot /></div>
</template>
```

`resources/js/components/ui/dialog/DialogTitle.vue`:
```vue
<script setup>
import { DialogTitle, useForwardProps } from "reka-ui";
import { cn } from "@/lib/utils";
const props = defineProps({ class: { type: null, required: false }, asChild: { type: Boolean, required: false }, as: { type: null, required: false } });
const delegatedProps = useForwardProps(props);
</script>

<template>
  <DialogTitle v-bind="delegatedProps" :class="cn('text-lg font-semibold text-darker-900', props.class)"><slot /></DialogTitle>
</template>
```

`resources/js/components/ui/dialog/DialogDescription.vue`:
```vue
<script setup>
import { DialogDescription, useForwardProps } from "reka-ui";
import { cn } from "@/lib/utils";
const props = defineProps({ class: { type: null, required: false }, asChild: { type: Boolean, required: false }, as: { type: null, required: false } });
const delegatedProps = useForwardProps(props);
</script>

<template>
  <DialogDescription v-bind="delegatedProps" :class="cn('text-sm text-muted-foreground', props.class)"><slot /></DialogDescription>
</template>
```

`resources/js/components/ui/dialog/index.js`:
```js
export { default as Dialog } from "./Dialog.vue";
export { default as DialogClose } from "./DialogClose.vue";
export { default as DialogContent } from "./DialogContent.vue";
export { default as DialogDescription } from "./DialogDescription.vue";
export { default as DialogFooter } from "./DialogFooter.vue";
export { default as DialogHeader } from "./DialogHeader.vue";
export { default as DialogTitle } from "./DialogTitle.vue";
export { default as DialogTrigger } from "./DialogTrigger.vue";
```

- [ ] **Step 2: Create the composable**

`resources/js/composables/useDebouncedSearch.js`:
```js
import { ref } from 'vue';

/**
 * Debounced JSON search against a URL that accepts a `?search=` param.
 * Returns reactive `results`/`loading` plus `search(term)` and `reset()`.
 */
export function useDebouncedSearch(searchUrl, { delay = 250 } = {}) {
    const results = ref([]);
    const loading = ref(false);
    let debounce_timer = null;

    const runFetch = async (term) => {
        loading.value = true;
        try {
            const url = new URL(searchUrl, window.location.origin);
            if (term) {
                url.searchParams.set('search', term);
            }
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            results.value = response.ok ? await response.json() : [];
        } catch (error) {
            results.value = [];
        } finally {
            loading.value = false;
        }
    };

    const search = (term = '') => {
        clearTimeout(debounce_timer);
        debounce_timer = setTimeout(() => runFetch(term), delay);
    };

    const reset = () => {
        clearTimeout(debounce_timer);
        results.value = [];
    };

    return { results, loading, search, reset };
}
```

- [ ] **Step 3: Refactor `UserSearchSelect.vue` onto the composable**

Replace its `users`/`loading`/`fetchUsers`/`onSearchInput` internals so the fetch+debounce comes from `useDebouncedSearch`, keeping template behaviour identical:
```vue
<script setup>
import { ref, watch } from 'vue';
import {
    Combobox, ComboboxAnchor, ComboboxContent, ComboboxEmpty,
    ComboboxInput, ComboboxItem, ComboboxList, ComboboxTrigger,
} from '@/components/ui/combobox';
import Avatar from '@/components/Avatar.vue';
import { useDebouncedSearch } from '@/composables/useDebouncedSearch';

const props = defineProps({
    searchUrl: { type: String, required: true },
    placeholder: { type: String, default: 'Search for a user…' },
    variant: {
        type: String, default: 'primary',
        validator: (value) => ['primary', 'accent', 'darker'].includes(value),
    },
});

const selectedValue = defineModel({ type: [Number, String, null], default: null });
const selectedUser = ref(null);
const { results: users, loading, search } = useDebouncedSearch(props.searchUrl);

const fullName = (user) => `${user.first_name ?? ''} ${user.last_name ?? ''}`.trim();
const onSearchInput = (event) => search(event.target.value);
const onOpenChange = (isOpen) => { if (isOpen) { search(''); } };

watch(selectedValue, (value) => {
    if (!value) { selectedUser.value = null; return; }
    const match = users.value.find((user) => user.id === value);
    if (match) { selectedUser.value = match; }
});
</script>
```
(Keep the existing `<template>` unchanged.)

- [ ] **Step 4: Refactor `GroupSearchSelect.vue` onto the composable**

Mirror Step 3: replace `groups`/`loading`/`fetchGroups`/`onSearchInput` with `const { results: groups, loading, search } = useDebouncedSearch(props.searchUrl);`, `const onSearchInput = (event) => search(event.target.value);`, `const onOpenChange = (isOpen) => { if (isOpen) { search(''); } };`. Keep the `<template>` unchanged.

- [ ] **Step 5: Verify the build**

Run: `npm run build`
Expected: builds with no "Unable to locate file in Vite manifest" / unresolved-import errors.

- [ ] **Step 6: Commit**

```bash
git add resources/js/components/ui/dialog resources/js/composables/useDebouncedSearch.js resources/js/components/UserSearchSelect.vue resources/js/components/GroupSearchSelect.vue
git commit -m "feat: add ui/dialog primitive and useDebouncedSearch composable"
```

---

## Task 2: Bulk-assign course instructors

**Files:**
- Create: `app/Actions/Courses/AssignInstructors.php`
- Modify: `app/Http/Requests/StoreCourseInstructorRequest.php`, `app/Http/Controllers/CourseInstructorController.php`
- Test: `tests/Feature/CourseInstructorControllerTest.php` (add cases + update existing array-shape posts)

**Interfaces:**
- Produces: `AssignInstructors::execute(Course $course, Collection $users): int` (count attached/restored). Store endpoint body `{ user_ids: number[] }`.
- Consumes: `App\Models\CourseUser` pivot (soft-deletes), `Course::instructors()`.

- [ ] **Step 1: Write the failing test (Action)**

Add to `tests/Feature/CourseInstructorControllerTest.php`:
```php
use App\Actions\Courses\AssignInstructors;
use Illuminate\Support\Collection;

it('bulk-attaches instructors, skipping already-assigned and restoring soft-deleted', function () {
    [$course, $existing] = courseWithInstructor();
    $fresh = userWithRole(UserRole::Instructor);
    $restorable = userWithRole(UserRole::Instructor);
    $course->instructors()->attach($restorable, ['is_instructor' => true]);
    $course->instructors()->detach($restorable); // soft-deletes the pivot

    $count = app(AssignInstructors::class)->execute(
        $course, new Collection([$fresh, $restorable, $existing])
    );

    expect($count)->toBe(2)
        ->and($course->instructors()->whereKey($fresh->id)->exists())->toBeTrue()
        ->and($course->instructors()->whereKey($restorable->id)->exists())->toBeTrue()
        ->and($course->instructors()->count())->toBe(3);
});
```

- [ ] **Step 2: Run it — expect fail**

Run: `php artisan test --compact --filter="bulk-attaches instructors"`
Expected: FAIL (class `AssignInstructors` not found).

- [ ] **Step 3: Implement `AssignInstructors`**

`app/Actions/Courses/AssignInstructors.php`:
```php
<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\CourseUser;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssignInstructors
{
    /**
     * Bulk-assign users to the course as instructors.
     *
     * Users with an active enrollment are skipped; a soft-deleted enrollment is
     * restored and flipped to instructor. Runs atomically.
     *
     * @param  Collection<int, User>  $users
     * @return int The number of instructors attached or restored.
     */
    public function execute(Course $course, Collection $users): int
    {
        return DB::transaction(function () use ($course, $users): int {
            $assigned_count = 0;

            foreach ($users as $user) {
                if ($this->assign($course, $user)) {
                    $assigned_count++;
                }
            }

            return $assigned_count;
        });
    }

    private function assign(Course $course, User $user): bool
    {
        $enrollment = CourseUser::withTrashed()
            ->where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();

        if ($enrollment === null) {
            $course->instructors()->attach($user, ['is_instructor' => true]);

            return true;
        }

        if ($enrollment->trashed()) {
            $enrollment->restore();
            $enrollment->update(['is_instructor' => true]);

            return true;
        }

        return false;
    }
}
```

- [ ] **Step 4: Run it — expect pass**

Run: `php artisan test --compact --filter="bulk-attaches instructors"`
Expected: PASS.

- [ ] **Step 5: Update the request to accept `user_ids[]`**

`app/Http/Requests/StoreCourseInstructorRequest.php` `rules()`:
```php
public function rules(): array
{
    return [
        'user_ids' => ['required', 'array', 'min:1'],
        'user_ids.*' => [
            'integer',
            'exists:users,id',
            function (string $attribute, mixed $value, callable $fail): void {
                $user = User::find($value);

                if ($user !== null && ! $user->hasAnyRole([UserRole::Admin, UserRole::Instructor])) {
                    $fail('Each selected user must be an instructor or admin.');
                }
            },
        ],
    ];
}
```
(Leave `authorize()` unchanged.)

- [ ] **Step 6: Update the controller `store`**

In `CourseInstructorController::store`, swap the body:
```php
public function store(StoreCourseInstructorRequest $request, Course $course, AssignInstructors $assignInstructors): RedirectResponse
{
    $users = User::findMany($request->validated()['user_ids']);

    $count = $assignInstructors->execute($course, $users);

    return redirect()
        ->route('courses.show', $course)
        ->with('success', "{$count} instructor(s) added successfully.");
}
```
Update the `use` imports: replace `AssignInstructor` with `AssignInstructors`.

- [ ] **Step 7: Write the failing endpoint test + fix existing single-id posts**

Update existing posts in this file that send `'user_id' => $x` to `'user_ids' => [$x]`, and change `assertSessionHasErrors('user_id')` to `assertSessionHasErrors('user_ids.*')` (for the wrong-role case) / drop the "already-assigned" rejection test (bulk skips duplicates instead — replace it with a skip assertion). Add:
```php
it('lets an admin add multiple instructors at once', function () {
    [$course] = courseWithInstructor();
    $a = userWithRole(UserRole::Instructor);
    $b = userWithRole(UserRole::Instructor);

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->post(route('courses.instructors.store', $course), ['user_ids' => [$a->id, $b->id]]);

    $response->assertRedirect(route('courses.show', $course));
    expect($course->instructors()->count())->toBe(3);
});

it('rejects the whole batch when any id is not an eligible role', function () {
    [$course] = courseWithInstructor();
    $ok = userWithRole(UserRole::Instructor);
    $bad = userWithRole(UserRole::Student);

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->post(route('courses.instructors.store', $course), ['user_ids' => [$ok->id, $bad->id]]);

    $response->assertSessionHasErrors('user_ids.*');
    expect($course->instructors()->whereKey($ok->id)->exists())->toBeFalse();
});
```

- [ ] **Step 8: Run the file — expect pass; then pint**

Run: `php artisan test --compact tests/Feature/CourseInstructorControllerTest.php`
Expected: PASS.
Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 9: Commit**

```bash
git add app/Actions/Courses/AssignInstructors.php app/Http/Requests/StoreCourseInstructorRequest.php app/Http/Controllers/CourseInstructorController.php tests/Feature/CourseInstructorControllerTest.php
git commit -m "feat: bulk-assign course instructors"
```

---

## Task 3: Bulk-assign course students

**Files:**
- Create: `app/Actions/Courses/AssignStudents.php`
- Modify: `app/Http/Requests/StoreCourseStudentRequest.php`, `app/Http/Controllers/CourseStudentController.php`
- Test: `tests/Feature/CourseStudentControllerTest.php`

**Interfaces:**
- Produces: `AssignStudents::execute(Course $course, Collection $users): int`. Store body `{ user_ids: number[] }`.

- [ ] **Step 1: Write the failing Action test**

Add to `tests/Feature/CourseStudentControllerTest.php`:
```php
use App\Actions\Courses\AssignStudents;
use Illuminate\Support\Collection;

it('bulk-enrolls students, skipping existing and restoring soft-deleted', function () {
    $course = Course::factory()->create();
    $fresh = userWithRole(UserRole::Student);
    $restorable = userWithRole(UserRole::Student);
    $course->students()->attach($restorable, ['is_instructor' => false]);
    $course->students()->detach($restorable);
    $already = userWithRole(UserRole::Student);
    $course->students()->attach($already, ['is_instructor' => false]);

    $count = app(AssignStudents::class)->execute(
        $course, new Collection([$fresh, $restorable, $already])
    );

    expect($count)->toBe(2)
        ->and($course->students()->count())->toBe(2);
});
```

- [ ] **Step 2: Run — expect fail**

Run: `php artisan test --compact --filter="bulk-enrolls students"`
Expected: FAIL (class not found).

- [ ] **Step 3: Implement `AssignStudents`**

`app/Actions/Courses/AssignStudents.php` — identical structure to `AssignInstructors` but attaching via `$course->students()->attach($user, ['is_instructor' => false])` and restore sets `['is_instructor' => false]`:
```php
<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\CourseUser;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssignStudents
{
    /**
     * Bulk-enroll users in the course as students.
     *
     * @param  Collection<int, User>  $users
     * @return int The number of students attached or restored.
     */
    public function execute(Course $course, Collection $users): int
    {
        return DB::transaction(function () use ($course, $users): int {
            $enrolled_count = 0;

            foreach ($users as $user) {
                if ($this->enroll($course, $user)) {
                    $enrolled_count++;
                }
            }

            return $enrolled_count;
        });
    }

    private function enroll(Course $course, User $user): bool
    {
        $enrollment = CourseUser::withTrashed()
            ->where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();

        if ($enrollment === null) {
            $course->students()->attach($user, ['is_instructor' => false]);

            return true;
        }

        if ($enrollment->trashed()) {
            $enrollment->restore();
            $enrollment->update(['is_instructor' => false]);

            return true;
        }

        return false;
    }
}
```

- [ ] **Step 4: Run — expect pass**

Run: `php artisan test --compact --filter="bulk-enrolls students"`
Expected: PASS.

- [ ] **Step 5: Update request to `user_ids[]`**

`StoreCourseStudentRequest::rules()`:
```php
public function rules(): array
{
    return [
        'user_ids' => ['required', 'array', 'min:1'],
        'user_ids.*' => [
            'integer',
            'exists:users,id',
            function (string $attribute, mixed $value, callable $fail): void {
                $user = User::find($value);

                if ($user !== null && ! $user->hasRole(UserRole::Student)) {
                    $fail('Each selected user must be a student.');
                }
            },
        ],
    ];
}
```

- [ ] **Step 6: Update controller `store`**

```php
public function store(StoreCourseStudentRequest $request, Course $course, AssignStudents $assignStudents): RedirectResponse
{
    $users = User::findMany($request->validated()['user_ids']);

    $count = $assignStudents->execute($course, $users);

    return redirect()
        ->route('courses.show', $course)
        ->with('success', "{$count} student(s) added successfully.");
}
```
Swap the `use App\Actions\Courses\AssignStudent;` import to `AssignStudents`.

- [ ] **Step 7: Update existing endpoint tests + add multi/reject cases**

Convert `'user_id' => $x` posts to `'user_ids' => [$x]`; adjust `assertSessionHasErrors('user_id')` → `assertSessionHasErrors('user_ids.*')`. Add an "adds multiple students at once" test and a "rejects batch with a non-student id" test mirroring Task 2 Step 7.

- [ ] **Step 8: Run file + pint**

Run: `php artisan test --compact tests/Feature/CourseStudentControllerTest.php`
Expected: PASS.
Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 9: Commit**

```bash
git add app/Actions/Courses/AssignStudents.php app/Http/Requests/StoreCourseStudentRequest.php app/Http/Controllers/CourseStudentController.php tests/Feature/CourseStudentControllerTest.php
git commit -m "feat: bulk-enroll course students"
```

---

## Task 4: Bulk-assign group members

**Files:**
- Create: `app/Actions/Groups/AssignMembers.php`
- Modify: `app/Http/Requests/StoreGroupMemberRequest.php`, `app/Http/Controllers/GroupMemberController.php`
- Test: `tests/Feature/GroupControllerTest.php` (member store cases)

**Interfaces:**
- Produces: `AssignMembers::execute(Group $group, Collection $users): int`. Store body `{ user_ids: number[] }`. Members are added as non-leaders; leader is toggled per-row afterwards (existing `update` route unchanged).

- [ ] **Step 1: Write the failing Action test**

Add to `tests/Feature/GroupControllerTest.php`:
```php
use App\Actions\Groups\AssignMembers;
use App\Models\Group;
use Illuminate\Support\Collection;

it('bulk-adds group members as non-leaders, skipping existing and restoring soft-deleted', function () {
    $group = Group::factory()->create();
    $fresh = userWithRole(UserRole::Student);
    $restorable = userWithRole(UserRole::Instructor);
    $group->users()->attach($restorable, ['is_leader' => false]);
    $group->users()->detach($restorable);
    $already = userWithRole(UserRole::Student);
    $group->users()->attach($already, ['is_leader' => false]);

    $count = app(AssignMembers::class)->execute(
        $group, new Collection([$fresh, $restorable, $already])
    );

    expect($count)->toBe(2)
        ->and($group->users()->count())->toBe(2)
        ->and($group->leaders()->count())->toBe(0);
});
```

- [ ] **Step 2: Run — expect fail**

Run: `php artisan test --compact --filter="bulk-adds group members"`
Expected: FAIL (class not found).

- [ ] **Step 3: Implement `AssignMembers`**

`app/Actions/Groups/AssignMembers.php` (uses the `GroupUser` pivot with soft-deletes):
```php
<?php

namespace App\Actions\Groups;

use App\Models\Group;
use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssignMembers
{
    /**
     * Bulk-add users to the group as non-leader members.
     *
     * @param  Collection<int, User>  $users
     * @return int The number of members attached or restored.
     */
    public function execute(Group $group, Collection $users): int
    {
        return DB::transaction(function () use ($group, $users): int {
            $added_count = 0;

            foreach ($users as $user) {
                if ($this->add($group, $user)) {
                    $added_count++;
                }
            }

            return $added_count;
        });
    }

    private function add(Group $group, User $user): bool
    {
        $membership = GroupUser::withTrashed()
            ->where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership === null) {
            $group->users()->attach($user, ['is_leader' => false]);

            return true;
        }

        if ($membership->trashed()) {
            $membership->restore();
            $membership->update(['is_leader' => false]);

            return true;
        }

        return false;
    }
}
```
(`GroupUser` extends `Base`, which already applies `SoftDeletes` — no trait change needed.)

- [ ] **Step 4: Run — expect pass**

Run: `php artisan test --compact --filter="bulk-adds group members"`
Expected: PASS.

- [ ] **Step 5: Update request to `user_ids[]` (drop `is_leader`)**

`StoreGroupMemberRequest::rules()`:
```php
public function rules(): array
{
    return [
        'user_ids' => ['required', 'array', 'min:1'],
        'user_ids.*' => [
            'integer',
            'exists:users,id',
            function (string $attribute, mixed $value, callable $fail): void {
                $user = User::find($value);

                if ($user !== null && ! $user->hasAnyRole([UserRole::Instructor, UserRole::Student])) {
                    $fail('Each selected user must be an instructor or student.');
                }
            },
        ],
    ];
}
```

- [ ] **Step 6: Update controller `store`**

```php
public function store(StoreGroupMemberRequest $request, Group $group, AssignMembers $assignMembers): RedirectResponse
{
    $users = User::findMany($request->validated()['user_ids']);

    $count = $assignMembers->execute($group, $users);

    return redirect()
        ->route('groups.show', $group)
        ->with('success', "{$count} member(s) added successfully.");
}
```
Swap the `use App\Actions\Groups\AssignMember;` import to `AssignMembers`.

- [ ] **Step 7: Update existing member-store tests + add cases**

Convert `'user_id'`/`is_leader` posts to `'user_ids' => [$x]`; adjust error-key assertions to `user_ids.*`. Add a multi-add test and a wrong-role reject test (mirroring Task 2).

- [ ] **Step 8: Run file + pint**

Run: `php artisan test --compact tests/Feature/GroupControllerTest.php`
Expected: PASS.
Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 9: Commit**

```bash
git add app/Actions/Groups/AssignMembers.php app/Http/Requests/StoreGroupMemberRequest.php app/Http/Controllers/GroupMemberController.php tests/Feature/GroupControllerTest.php
git commit -m "feat: bulk-add group members"
```

---

## Task 5: Bulk-enroll multiple groups into a course

**Files:**
- Create: `app/Actions/Courses/EnrollGroups.php`
- Modify: `app/Http/Requests/StoreCourseGroupStudentsRequest.php`, `app/Http/Controllers/CourseStudentController.php`
- Test: `tests/Feature/CourseStudentControllerTest.php` (or `EnrollGroupMembersTest.php`)

**Interfaces:**
- Consumes: existing `EnrollGroupMembers::execute(Course, Group): int`.
- Produces: `EnrollGroups::execute(Course $course, Collection $groups): int`. `storeGroup` body `{ group_ids: number[] }`.

- [ ] **Step 1: Write the failing Action test**

Add to `tests/Feature/CourseStudentControllerTest.php`:
```php
use App\Actions\Courses\EnrollGroups;
use App\Models\Group;
use Illuminate\Support\Collection;

it('enrolls the student members of several groups into a course', function () {
    $course = Course::factory()->create();
    $group_one = Group::factory()->create();
    $group_two = Group::factory()->create();
    $student_one = userWithRole(UserRole::Student);
    $student_two = userWithRole(UserRole::Student);
    $group_one->users()->attach($student_one, ['is_leader' => false]);
    $group_two->users()->attach($student_two, ['is_leader' => false]);

    $count = app(EnrollGroups::class)->execute(
        $course, new Collection([$group_one, $group_two])
    );

    expect($count)->toBe(2)
        ->and($course->students()->count())->toBe(2);
});
```

- [ ] **Step 2: Run — expect fail**

Run: `php artisan test --compact --filter="enrolls the student members of several groups"`
Expected: FAIL (class not found).

- [ ] **Step 3: Implement `EnrollGroups`**

`app/Actions/Courses/EnrollGroups.php`:
```php
<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\Group;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EnrollGroups
{
    public function __construct(private EnrollGroupMembers $enrollGroupMembers) {}

    /**
     * Bulk-enroll each group's current student members into the course.
     *
     * @param  Collection<int, Group>  $groups
     * @return int The total number of members enrolled across all groups.
     */
    public function execute(Course $course, Collection $groups): int
    {
        return DB::transaction(fn (): int => $groups->sum(
            fn (Group $group): int => $this->enrollGroupMembers->execute($course, $group)
        ));
    }
}
```

- [ ] **Step 4: Run — expect pass**

Run: `php artisan test --compact --filter="enrolls the student members of several groups"`
Expected: PASS.

- [ ] **Step 5: Update request to `group_ids[]`**

`StoreCourseGroupStudentsRequest::rules()`:
```php
public function rules(): array
{
    return [
        'group_ids' => ['required', 'array', 'min:1'],
        'group_ids.*' => ['integer', 'exists:groups,id'],
    ];
}
```

- [ ] **Step 6: Update controller `storeGroup`**

```php
public function storeGroup(StoreCourseGroupStudentsRequest $request, Course $course, EnrollGroups $enrollGroups): RedirectResponse
{
    $groups = Group::findMany($request->validated()['group_ids']);

    $enrolled_count = $enrollGroups->execute($course, $groups);

    return redirect()
        ->route('courses.show', $course)
        ->with('success', "{$enrolled_count} member(s) enrolled from the selected group(s).");
}
```
Swap the `use App\Actions\Courses\EnrollGroupMembers;` import to `EnrollGroups`.

- [ ] **Step 7: Update the endpoint test to `group_ids[]`**

Convert any `'group_id' => $g->id` post to `'group_ids' => [$g->id]`.

- [ ] **Step 8: Run file + pint**

Run: `php artisan test --compact tests/Feature/CourseStudentControllerTest.php`
Expected: PASS.
Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 9: Commit**

```bash
git add app/Actions/Courses/EnrollGroups.php app/Http/Requests/StoreCourseGroupStudentsRequest.php app/Http/Controllers/CourseStudentController.php tests/Feature/CourseStudentControllerTest.php
git commit -m "feat: bulk-enroll multiple groups into a course"
```

---

## Task 6: Roster `index` endpoints (paginated + searchable)

**Files:**
- Create: `app/Actions/Courses/SearchCourseInstructors.php`, `app/Actions/Courses/SearchCourseStudents.php`, `app/Actions/Groups/SearchGroupMembers.php`
- Create: `app/Http/Requests/IndexCourseRosterRequest.php`, `app/Http/Requests/IndexGroupMembersRequest.php`
- Modify: `app/Http/Controllers/CourseInstructorController.php`, `app/Http/Controllers/CourseStudentController.php`, `app/Http/Controllers/GroupMemberController.php`, `routes/web.php`
- Test: the three controller test files

**Interfaces:**
- Produces routes `courses.instructors.index`, `courses.students.index`, `groups.members.index` returning a Laravel paginator JSON (`data`, `current_page`, `last_page`, `total`). Actions: `Search*::execute(Model $model, ?string $search, int $perPage = 25): LengthAwarePaginator`. Group member rows include the `is_leader` pivot.

- [ ] **Step 1: Write the failing endpoint tests**

Add to `tests/Feature/CourseInstructorControllerTest.php`:
```php
it('paginates and searches the instructor roster for a manager', function () {
    [$course, $assigned] = courseWithInstructor();
    $match = userWithRole(UserRole::Instructor);
    $match->update(['first_name' => 'Rosterable', 'last_name' => 'Person']);
    $course->instructors()->attach($match, ['is_instructor' => true]);

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->getJson(route('courses.instructors.index', ['course' => $course, 'search' => 'Rosterable']));

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($match->id)->not->toContain($assigned->id);
});

it('forbids a non-manager from listing the instructor roster', function () {
    [$course] = courseWithInstructor();
    $this->actingAs(userWithRole(UserRole::Student))
        ->getJson(route('courses.instructors.index', $course))
        ->assertForbidden();
});
```
Add parallel tests to `CourseStudentControllerTest.php` (`courses.students.index`) and `GroupControllerTest.php` (`groups.members.index`, asserting each row exposes `pivot.is_leader`).

- [ ] **Step 2: Run — expect fail**

Run: `php artisan test --compact --filter="roster"`
Expected: FAIL (route/method not defined).

- [ ] **Step 3: Implement the three search Actions**

`app/Actions/Courses/SearchCourseInstructors.php`:
```php
<?php

namespace App\Actions\Courses;

use App\Models\Course;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchCourseInstructors
{
    /**
     * Paginate the course's instructor roster, optionally filtered by a
     * name/email search term.
     */
    public function execute(Course $course, ?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        return $course->instructors()
            ->when($search, fn ($query, $term) => $query->where(fn ($builder) => $builder
                ->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")))
            ->with('media')
            ->orderBy('first_name')
            ->paginate($perPage, ['users.id', 'users.first_name', 'users.last_name', 'users.email']);
    }
}
```
`SearchCourseStudents.php` is identical but calls `$course->students()`. `app/Actions/Groups/SearchGroupMembers.php` uses `$group->users()` and selects `['users.id', 'users.first_name', 'users.last_name', 'users.email']` (the `is_leader` pivot is included automatically by the `users()` `withPivot`).

- [ ] **Step 4: Create the index request classes**

`app/Http/Requests/IndexCourseRosterRequest.php`:
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexCourseRosterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('course'));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
```
`app/Http/Requests/IndexGroupMembersRequest.php` mirrors it with `authorize()` returning `$this->user()->can('manageMembers', $this->route('group'))`.

> Note: instructor and student roster endpoints both use `IndexCourseRosterRequest`; `CoursePolicy::update` already gates instructor/student management. If you prefer per-ability gating, add `manageInstructors`/`manageStudents` checks in the controller instead.

- [ ] **Step 5: Add controller `index` methods**

`CourseInstructorController`:
```php
public function index(IndexCourseRosterRequest $request, Course $course, SearchCourseInstructors $searchCourseInstructors): JsonResponse
{
    return response()->json(
        $searchCourseInstructors->execute($course, $request->string('search')->toString() ?: null)
    );
}
```
Add the same shape to `CourseStudentController::index` (using `SearchCourseStudents`) and `GroupMemberController::index` (using `IndexGroupMembersRequest` + `SearchGroupMembers`). Add the needed `use` imports.

- [ ] **Step 6: Register the routes**

In `routes/web.php`, add inside the courses group (near the existing instructor/student routes):
```php
// Search/paginate the enrolled instructor roster
Route::get('/{course}/instructors', [CourseInstructorController::class, 'index'])->name('instructors.index');

// Search/paginate the enrolled student roster
Route::get('/{course}/students', [CourseStudentController::class, 'index'])->name('students.index');
```
And inside the groups group:
```php
// Search/paginate the group's member roster
Route::get('/{group}/members', [GroupMemberController::class, 'index'])->name('members.index');
```
> Ordering: register `.../instructors/assignable` and `.../students/assignable` BEFORE these bare collection routes are fine (they are literal segments), but ensure `/{course}/students` does not shadow `/{course}/students/assignable` — literal `assignable` routes are already declared and Laravel matches the more specific literal first regardless of order. Verify with `php artisan route:list --path=students`.

- [ ] **Step 7: Run tests — expect pass**

Run: `php artisan test --compact --filter="roster"`
Expected: PASS.
Run: `php artisan route:list --path=instructors` and confirm `instructors.index` + `instructors.assignable` both resolve.

- [ ] **Step 8: pint + commit**

Run: `vendor/bin/pint --dirty --format agent`
```bash
git add app/Actions/Courses/SearchCourseInstructors.php app/Actions/Courses/SearchCourseStudents.php app/Actions/Groups/SearchGroupMembers.php app/Http/Requests/IndexCourseRosterRequest.php app/Http/Requests/IndexGroupMembersRequest.php app/Http/Controllers/CourseInstructorController.php app/Http/Controllers/CourseStudentController.php app/Http/Controllers/GroupMemberController.php routes/web.php tests/Feature/CourseInstructorControllerTest.php tests/Feature/CourseStudentControllerTest.php tests/Feature/GroupControllerTest.php
git commit -m "feat: paginated searchable roster index endpoints"
```

---

## Task 7: Bound the initial roster load

**Files:**
- Modify: `app/Actions/Courses/LoadCourseDetails.php`, `app/Actions/Groups/LoadGroupDetails.php`
- Test: `tests/Feature/CourseControllerTest.php`, `tests/Feature/GroupControllerTest.php`

**Interfaces:**
- Produces: `Courses/Show` prop `course.instructors`/`course.students` contain at most 25 rows; `course.instructors_count`/`students_count` reflect totals. `Groups/Show` prop `group.users` ≤ 25 with `group.users_count` total.

- [ ] **Step 1: Write the failing bounded-load test**

Add to `tests/Feature/CourseControllerTest.php`:
```php
use Inertia\Testing\AssertableInertia;

it('loads only the first page of the student roster with the full count', function () {
    $course = Course::factory()->create();
    $instructor = userWithRole(UserRole::Instructor);
    $course->instructors()->attach($instructor, ['is_instructor' => true]);
    $students = collect(range(1, 30))->map(fn () => userWithRole(UserRole::Student));
    $students->each(fn ($student) => $course->students()->attach($student, ['is_instructor' => false]));

    $this->actingAs($instructor)
        ->get(route('courses.show', $course))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('course.students', 25)
            ->where('course.students_count', 30));
});
```
Add the parallel test to `GroupControllerTest.php` (`group.users` capped at 25, `group.users_count` = 30).

- [ ] **Step 2: Run — expect fail**

Run: `php artisan test --compact --filter="loads only the first page"`
Expected: FAIL (currently loads all 30).

- [ ] **Step 3: Bound the eager loads**

In `LoadCourseDetails::execute`, add `->orderBy('first_name')->limit(25)` to the `instructors` and `students` closures:
```php
'instructors' => function ($query) {
    $query->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
        ->with('media')->orderBy('first_name')->limit(25);
},
'students' => function ($query) {
    $query->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
        ->with('media')->orderBy('first_name')->limit(25);
},
```
In `LoadGroupDetails::execute`, add `->limit(25)` to the `users` closure (it already orders by `first_name`).

- [ ] **Step 4: Run — expect pass**

Run: `php artisan test --compact --filter="loads only the first page"`
Expected: PASS.

- [ ] **Step 5: pint + commit**

Run: `vendor/bin/pint --dirty --format agent`
```bash
git add app/Actions/Courses/LoadCourseDetails.php app/Actions/Groups/LoadGroupDetails.php tests/Feature/CourseControllerTest.php tests/Feature/GroupControllerTest.php
git commit -m "feat: bound initial roster load to first page"
```

---

## Task 8: `AssignDialog` + `AssignGroupsDialog` components

**Files:**
- Create: `resources/js/components/AssignDialog.vue`, `resources/js/components/AssignGroupsDialog.vue`

**Interfaces:**
- Consumes: `ui/dialog`, `useDebouncedSearch`, existing `*.assignable` endpoints, bulk `*.store`/`students.storeGroup` endpoints.
- Produces: `AssignDialog` props `{ title, description?, searchUrl, storeUrl, variant?, triggerLabel? }` posting `{ user_ids }`. `AssignGroupsDialog` props `{ title, searchUrl, storeUrl, triggerLabel? }` posting `{ group_ids }`.

- [ ] **Step 1: Create `AssignDialog.vue`**

```vue
<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { UserPlus, Search, Check } from 'lucide-vue-next';
import {
    Dialog, DialogTrigger, DialogContent, DialogHeader,
    DialogFooter, DialogTitle, DialogDescription, DialogClose,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import Avatar from '@/components/Avatar.vue';
import { useDebouncedSearch } from '@/composables/useDebouncedSearch';

const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    searchUrl: { type: String, required: true },
    storeUrl: { type: String, required: true },
    variant: { type: String, default: 'primary' },
    triggerLabel: { type: String, default: 'Add' },
});

const open = ref(false);
const selected = ref([]); // array of user ids
const submitting = ref(false);
const { results: users, loading, search, reset } = useDebouncedSearch(props.searchUrl);

const fullName = (user) => `${user.first_name ?? ''} ${user.last_name ?? ''}`.trim();
const isSelected = (id) => selected.value.includes(id);

const toggle = (id) => {
    selected.value = isSelected(id)
        ? selected.value.filter((value) => value !== id)
        : [...selected.value, id];
};

const onOpenChange = (value) => {
    open.value = value;
    if (value) {
        selected.value = [];
        search('');
    } else {
        reset();
    }
};

const submit = () => {
    if (selected.value.length === 0) {
        return;
    }
    submitting.value = true;
    router.post(props.storeUrl, { user_ids: selected.value }, {
        preserveScroll: true,
        onSuccess: () => { open.value = false; },
        onFinish: () => { submitting.value = false; },
    });
};
</script>

<template>
  <Dialog :open="open" @update:open="onOpenChange">
    <DialogTrigger as-child>
      <Button :variant="variant === 'accent' ? 'default' : 'default'">
        <UserPlus class="w-4 h-4" />
        {{ triggerLabel }}
      </Button>
    </DialogTrigger>
    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ title }}</DialogTitle>
        <DialogDescription v-if="description">{{ description }}</DialogDescription>
      </DialogHeader>

      <div class="relative">
        <Search class="pointer-events-none absolute left-3 top-1/2 w-4 h-4 -translate-y-1/2 text-darker-400" />
        <input
            type="text"
            class="w-full rounded-md border border-darker-200 bg-white py-2 pl-9 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400"
            placeholder="Search by name or email…"
            @input="search($event.target.value)"
        />
      </div>

      <div class="max-h-72 space-y-1 overflow-y-auto">
        <p v-if="loading" class="py-6 text-center text-sm text-darker-500">Searching…</p>
        <p v-else-if="users.length === 0" class="py-6 text-center text-sm text-darker-500">No matches found.</p>
        <button
            v-for="user in users"
            :key="user.id"
            type="button"
            class="flex w-full items-center justify-between gap-3 rounded-lg p-2 text-left hover:bg-darker-50"
            :class="isSelected(user.id) ? 'bg-primary-50 ring-1 ring-primary-200' : ''"
            @click="toggle(user.id)"
        >
          <span class="flex items-center gap-3">
            <Avatar :user="user" size="sm" :variant="variant" :zoomable="false" />
            <span>
              <span class="block font-medium text-darker-900">{{ fullName(user) }}</span>
              <span class="block text-xs text-darker-600">{{ user.email }}</span>
            </span>
          </span>
          <Check v-if="isSelected(user.id)" class="w-4 h-4 text-primary-600" />
        </button>
      </div>

      <DialogFooter>
        <DialogClose as-child>
          <Button variant="outline">Cancel</Button>
        </DialogClose>
        <Button :disabled="selected.length === 0 || submitting" @click="submit">
          Add<span v-if="selected.length"> ({{ selected.length }})</span>
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
```

- [ ] **Step 2: Create `AssignGroupsDialog.vue`**

Same shell, but list groups (name + description), track selected group ids, and post `{ group_ids }`:
```vue
<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Users, Search, Check } from 'lucide-vue-next';
import {
    Dialog, DialogTrigger, DialogContent, DialogHeader,
    DialogFooter, DialogTitle, DialogDescription, DialogClose,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { useDebouncedSearch } from '@/composables/useDebouncedSearch';

const props = defineProps({
    title: { type: String, required: true },
    searchUrl: { type: String, required: true },
    storeUrl: { type: String, required: true },
    triggerLabel: { type: String, default: 'Add a group' },
});

const open = ref(false);
const selected = ref([]);
const submitting = ref(false);
const { results: groups, loading, search, reset } = useDebouncedSearch(props.searchUrl);

const isSelected = (id) => selected.value.includes(id);
const toggle = (id) => {
    selected.value = isSelected(id)
        ? selected.value.filter((value) => value !== id)
        : [...selected.value, id];
};

const onOpenChange = (value) => {
    open.value = value;
    if (value) { selected.value = []; search(''); } else { reset(); }
};

const submit = () => {
    if (selected.value.length === 0) { return; }
    submitting.value = true;
    router.post(props.storeUrl, { group_ids: selected.value }, {
        preserveScroll: true,
        onSuccess: () => { open.value = false; },
        onFinish: () => { submitting.value = false; },
    });
};
</script>

<template>
  <Dialog :open="open" @update:open="onOpenChange">
    <DialogTrigger as-child>
      <Button variant="outline">
        <Users class="w-4 h-4" />
        {{ triggerLabel }}
      </Button>
    </DialogTrigger>
    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ title }}</DialogTitle>
        <DialogDescription>Every current student member of the selected group(s) will be enrolled.</DialogDescription>
      </DialogHeader>

      <div class="relative">
        <Search class="pointer-events-none absolute left-3 top-1/2 w-4 h-4 -translate-y-1/2 text-darker-400" />
        <input
            type="text"
            class="w-full rounded-md border border-darker-200 bg-white py-2 pl-9 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400"
            placeholder="Search groups…"
            @input="search($event.target.value)"
        />
      </div>

      <div class="max-h-72 space-y-1 overflow-y-auto">
        <p v-if="loading" class="py-6 text-center text-sm text-darker-500">Searching…</p>
        <p v-else-if="groups.length === 0" class="py-6 text-center text-sm text-darker-500">No groups found.</p>
        <button
            v-for="group in groups"
            :key="group.id"
            type="button"
            class="flex w-full items-center justify-between gap-3 rounded-lg p-2 text-left hover:bg-darker-50"
            :class="isSelected(group.id) ? 'bg-primary-50 ring-1 ring-primary-200' : ''"
            @click="toggle(group.id)"
        >
          <span>
            <span class="block font-medium text-darker-900">{{ group.name }}</span>
            <span v-if="group.description" class="block text-xs text-darker-600 truncate">{{ group.description }}</span>
          </span>
          <Check v-if="isSelected(group.id)" class="w-4 h-4 text-primary-600" />
        </button>
      </div>

      <DialogFooter>
        <DialogClose as-child>
          <Button variant="outline">Cancel</Button>
        </DialogClose>
        <Button :disabled="selected.length === 0 || submitting" @click="submit">
          Enroll<span v-if="selected.length"> ({{ selected.length }})</span>
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
```

- [ ] **Step 3: Verify build**

Run: `npm run build`
Expected: builds cleanly.

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/AssignDialog.vue resources/js/components/AssignGroupsDialog.vue
git commit -m "feat: add multi-select assign dialogs"
```

---

## Task 9: `RosterList` component (hybrid filter)

**Files:**
- Create: `resources/js/components/RosterList.vue`

**Interfaces:**
- Consumes: `ScrollableList`, `useDebouncedSearch`, a roster `index` endpoint.
- Produces: props `{ items: Array, count: Number, searchUrl: String, variant?, emptyText? }`; a `#actions` scoped slot receiving `{ person }` for per-row controls. Owns instant client filter + backend fallback + load-more.

- [ ] **Step 1: Create `RosterList.vue`**

```vue
<script setup>
import { ref, computed, watch } from 'vue';
import { Search, Users } from 'lucide-vue-next';
import ScrollableList from '@/components/ScrollableList.vue';
import Avatar from '@/components/Avatar.vue';
import { Button } from '@/components/ui/button';
import { useDebouncedSearch } from '@/composables/useDebouncedSearch';

const props = defineProps({
    items: { type: Array, default: () => [] },
    count: { type: Number, default: 0 },
    searchUrl: { type: String, required: true },
    variant: { type: String, default: 'primary' },
    emptyText: { type: String, default: 'No one here yet' },
});

const loaded = ref([...props.items]);
const nextPage = ref(2);
const term = ref('');
const { results: serverPage, loading, search: runServerSearch, reset } = useDebouncedSearch(props.searchUrl);

// Re-sync when Inertia refreshes the page props (e.g. after add/remove).
watch(() => props.items, (items) => { loaded.value = [...items]; nextPage.value = 2; });

const fullName = (person) => `${person.first_name ?? ''} ${person.last_name ?? ''}`.trim();
const matches = (person, needle) =>
    fullName(person).toLowerCase().includes(needle) ||
    (person.email ?? '').toLowerCase().includes(needle);

const hasMoreOnServer = computed(() => props.count > loaded.value.length);

const displayed = computed(() => {
    const needle = term.value.trim().toLowerCase();
    if (!needle) {
        return loaded.value;
    }
    const local = loaded.value.filter((person) => matches(person, needle));
    // If the roster is larger than what we hold, prefer server results.
    if (hasMoreOnServer.value && serverPage.value.data) {
        return serverPage.value.data;
    }
    return local;
});

watch(term, (value) => {
    const needle = value.trim();
    if (needle && hasMoreOnServer.value) {
        runServerSearch(needle);
    } else {
        reset();
    }
});

const loadMore = async () => {
    const url = new URL(props.searchUrl, window.location.origin);
    url.searchParams.set('page', nextPage.value);
    const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    if (!response.ok) { return; }
    const payload = await response.json();
    loaded.value = [...loaded.value, ...(payload.data ?? [])];
    nextPage.value += 1;
};
</script>

<template>
  <div>
    <div v-if="count > 0" class="relative mb-3">
      <Search class="pointer-events-none absolute left-3 top-1/2 w-4 h-4 -translate-y-1/2 text-darker-400" />
      <input
          v-model="term"
          type="text"
          class="w-full rounded-md border border-darker-200 bg-white py-2 pl-9 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400"
          placeholder="Filter by name or email…"
      />
    </div>

    <ScrollableList v-if="displayed.length > 0">
      <div
          v-for="person in displayed"
          :key="person.id"
          class="flex items-center justify-between gap-3 p-3 bg-darker-50 rounded-lg"
      >
        <div class="flex items-center gap-3">
          <Avatar :user="person" :variant="variant" />
          <div>
            <p class="font-semibold text-darker-900">{{ fullName(person) }}</p>
            <p class="text-sm text-darker-600">{{ person.email }}</p>
          </div>
        </div>
        <slot name="actions" :person="person" />
      </div>

      <div v-if="!term && hasMoreOnServer" class="pt-1 text-center">
        <Button variant="ghost" size="sm" :disabled="loading" @click="loadMore">Load more</Button>
      </div>
    </ScrollableList>

    <div v-else class="text-center py-8 text-darker-500">
      <Users class="w-10 h-10 mb-3 mx-auto" />
      <p>{{ term ? 'No matches.' : emptyText }}</p>
    </div>
  </div>
</template>
```

- [ ] **Step 2: Verify build**

Run: `npm run build`
Expected: builds cleanly.

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/RosterList.vue
git commit -m "feat: add RosterList with hybrid client/server filtering"
```

---

## Task 10: Wire `Courses/Show.vue`

**Files:**
- Modify: `resources/js/Pages/Courses/Show.vue`
- Test: `tests/Feature/CourseControllerTest.php` (smoke: show still 200s)

**Interfaces:**
- Consumes: `AssignDialog`, `AssignGroupsDialog`, `RosterList`, roster `index` + bulk store routes.

- [ ] **Step 1: Replace imports and drop inline add-state**

In `<script setup>`, remove `ScrollableList`, `UserSearchSelect`, `GroupSearchSelect` imports and the `selected_instructor_id`/`selected_student_id`/`selected_group_id`/`addInstructor`/`addStudent`/`addGroup` code. Add:
```js
import RosterList from '@/components/RosterList.vue';
import AssignDialog from '@/components/AssignDialog.vue';
import AssignGroupsDialog from '@/components/AssignGroupsDialog.vue';
```
Keep `removeInstructor`/`removeStudent`.

- [ ] **Step 2: Rewrite the Instructors card body**

```vue
<CardContent>
  <RosterList
      :items="course.instructors ?? []"
      :count="course.instructors_count"
      :search-url="route('courses.instructors.index', course.id)"
      variant="primary"
      empty-text="No instructors assigned yet"
  >
    <template #actions="{ person }">
      <Button
          v-if="canManageInstructors"
          variant="ghost" size="icon-sm"
          class="text-destructive hover:bg-destructive/10 disabled:opacity-30"
          :disabled="course.instructors_count === 1"
          :aria-label="`Remove ${person.first_name} ${person.last_name}`"
          @click="removeInstructor(person)"
      >
        <X class="w-4 h-4" />
      </Button>
    </template>
  </RosterList>

  <div v-if="canManageInstructors" class="mt-4 pt-4 border-t border-darker-200">
    <AssignDialog
        title="Add instructors"
        description="Search instructors and admins to add to this course."
        :search-url="route('courses.instructors.assignable', course.id)"
        :store-url="route('courses.instructors.store', course.id)"
        variant="primary"
        trigger-label="Add instructors"
    />
  </div>
</CardContent>
```

- [ ] **Step 3: Rewrite the Students card body**

```vue
<CardContent>
  <RosterList
      :items="course.students ?? []"
      :count="course.students_count"
      :search-url="route('courses.students.index', course.id)"
      variant="accent"
      empty-text="No students enrolled yet"
  >
    <template #actions="{ person }">
      <Button
          v-if="canManageStudents"
          variant="ghost" size="icon-sm"
          class="text-destructive hover:bg-destructive/10"
          :aria-label="`Remove ${person.first_name} ${person.last_name}`"
          @click="removeStudent(person)"
      >
        <X class="w-4 h-4" />
      </Button>
    </template>
  </RosterList>

  <div v-if="canManageStudents" class="mt-4 pt-4 border-t border-darker-200 flex flex-wrap gap-2">
    <AssignDialog
        title="Add students"
        description="Search students to enroll in this course."
        :search-url="route('courses.students.assignable', course.id)"
        :store-url="route('courses.students.store', course.id)"
        variant="accent"
        trigger-label="Add students"
    />
    <AssignGroupsDialog
        title="Enroll a group"
        :search-url="route('courses.students.assignable-groups', course.id)"
        :store-url="route('courses.students.storeGroup', course.id)"
        trigger-label="Add a group"
    />
  </div>
</CardContent>
```

- [ ] **Step 4: Verify build + show route**

Run: `npm run build`
Expected: clean build.
Run: `php artisan test --compact tests/Feature/CourseControllerTest.php`
Expected: PASS (show page still renders).

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Courses/Show.vue
git commit -m "feat: modal enrollment + roster filter on course page"
```

---

## Task 11: Wire `Groups/Show.vue`

**Files:**
- Modify: `resources/js/Pages/Groups/Show.vue`
- Test: `tests/Feature/GroupControllerTest.php` (smoke)

- [ ] **Step 1: Replace imports and drop inline add-state**

Remove `UserSearchSelect`, `Checkbox`, `Label` imports and `selected_user_id`/`add_as_leader`/`addMember`. Add:
```js
import RosterList from '@/components/RosterList.vue';
import AssignDialog from '@/components/AssignDialog.vue';
```
Keep `toggleLeader`/`removeMember`.

- [ ] **Step 2: Rewrite the Members card body**

```vue
<CardContent>
  <RosterList
      :items="group.users ?? []"
      :count="group.users_count"
      :search-url="route('groups.members.index', group.id)"
      variant="primary"
      empty-text="No members yet"
  >
    <template #actions="{ person }">
      <div v-if="canManageMembers" class="flex items-center gap-1">
        <Button
            variant="ghost" size="icon-sm"
            :class="person.pivot?.is_leader ? 'text-amber-500 hover:bg-amber-500/10' : 'text-darker-400 hover:bg-darker-200'"
            :aria-label="person.pivot?.is_leader ? `Demote ${person.first_name}` : `Promote ${person.first_name} to leader`"
            :title="person.pivot?.is_leader ? 'Remove leader' : 'Make leader'"
            @click="toggleLeader(person)"
        >
          <Star class="w-4 h-4" />
        </Button>
        <Button
            variant="ghost" size="icon-sm"
            class="text-destructive hover:bg-destructive/10"
            :aria-label="`Remove ${person.first_name} ${person.last_name}`"
            title="Remove member"
            @click="removeMember(person)"
        >
          <X class="w-4 h-4" />
        </Button>
      </div>
    </template>
  </RosterList>

  <div v-if="canManageMembers" class="mt-4 pt-4 border-t border-darker-200">
    <AssignDialog
        title="Add members"
        description="Search instructors and students to add to this group."
        :search-url="route('groups.members.assignable', group.id)"
        :store-url="route('groups.members.store', group.id)"
        variant="primary"
        trigger-label="Add members"
    />
  </div>
</CardContent>
```
> The leader `Badge` shown next to a member's name in the old markup moves into the `#actions` area or can be re-added inside a custom row; to keep the leader badge, render it in the `RosterList` default row you can instead expose it via the slot. Minimal approach: rely on the star toggle colour to indicate leader; if the badge is required, add a `#badge` slot to `RosterList` in Task 9 and use it here.

- [ ] **Step 3: Verify build + show route**

Run: `npm run build`
Expected: clean build.
Run: `php artisan test --compact tests/Feature/GroupControllerTest.php`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Groups/Show.vue
git commit -m "feat: modal member add + roster filter on group page"
```

---

## Task 12: Full verification

- [ ] **Step 1: Full backend suite**

Run: `php artisan test --compact`
Expected: all green. Fix any test still posting `user_id`/`is_leader`/`group_id` singular shapes.

- [ ] **Step 2: Build + lint**

Run: `npm run build` and `vendor/bin/pint --format agent`
Expected: clean.

- [ ] **Step 3: Manual smoke (optional)**

With `composer run dev` running, open a course show page as an admin: verify the roster filter finds a loaded member instantly and a not-yet-loaded member via server, and that "Add instructors"/"Add students"/"Add a group" modals multi-select and enroll.

---

## Self-Review Notes

- **Spec coverage:** modal add (Tasks 8,10,11) ✓; multi-select + bulk store (2–5) ✓; separate group dialog (5,8,10) ✓; hybrid roster filter (6,9) ✓; bounded initial load (7) ✓; `useDebouncedSearch` de-dup (1) ✓; policies unchanged (reused in requests) ✓; enums (all requests use `UserRole`) ✓.
- **Breaking change flagged:** store request bodies change from singular (`user_id`/`group_id`/`is_leader`) to arrays (`user_ids`/`group_ids`); existing tests are updated in the same task that changes each request.
- **Behavior change flagged:** bulk validation rejects wrong-role ids atomically but no longer errors on already-enrolled ids (the Action skips them) — matches spec.
- **Type consistency:** Actions expose `execute(Model, Collection): int`; controllers pass `User::findMany(...)`/`Group::findMany(...)`; frontend posts `{ user_ids }` / `{ group_ids }` matching the request keys.
