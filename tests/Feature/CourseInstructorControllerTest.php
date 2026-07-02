<?php

use App\Actions\Courses\AssignInstructor;
use App\Actions\Courses\AssignInstructors;
use App\Actions\Courses\RemoveInstructor;
use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

it('assigns the creator as an instructor when a course is created', function () {
    $creator = userWithRole(UserRole::Instructor);

    $this->actingAs($creator)->post(route('courses.store'), [
        'status' => CourseStatus::Draft->value,
        'title' => 'Creator Course',
        'code' => 'CREATE-101',
    ]);

    $course = Course::firstWhere('code', 'CREATE-101');

    expect($course)->not->toBeNull()
        ->and($course->instructors()->whereKey($creator->id)->exists())->toBeTrue()
        ->and($course->instructors()->count())->toBe(1);
});

it('authorizes admins and assigned instructors to manage instructors', function () {
    $course = Course::factory()->create();
    $instructor = userWithRole(UserRole::Instructor);
    $course->instructors()->attach($instructor, ['is_instructor' => true]);

    $admin = userWithRole(UserRole::Admin);
    $other_instructor = userWithRole(UserRole::Instructor);
    $student = userWithRole(UserRole::Student);

    expect($admin->can('manageInstructors', $course))->toBeTrue()
        ->and($instructor->can('manageInstructors', $course))->toBeTrue()
        ->and($other_instructor->can('manageInstructors', $course))->toBeFalse()
        ->and($student->can('manageInstructors', $course))->toBeFalse();
});

it('attaches a user as an instructor via the AssignInstructor action', function () {
    $course = Course::factory()->create();
    $user = userWithRole(UserRole::Instructor);

    app(AssignInstructor::class)->execute($course, $user);

    expect($course->instructors()->whereKey($user->id)->exists())->toBeTrue();
});

it('detaches a non-last instructor via the RemoveInstructor action', function () {
    $course = Course::factory()->create();
    $keep = userWithRole(UserRole::Instructor);
    $remove = userWithRole(UserRole::Instructor);
    $course->instructors()->attach($keep, ['is_instructor' => true]);
    $course->instructors()->attach($remove, ['is_instructor' => true]);

    app(RemoveInstructor::class)->execute($course, $remove);

    expect($course->instructors()->whereKey($remove->id)->exists())->toBeFalse()
        ->and($course->instructors()->count())->toBe(1);
});

it('refuses to remove the last instructor', function () {
    $course = Course::factory()->create();
    $only = userWithRole(UserRole::Instructor);
    $course->instructors()->attach($only, ['is_instructor' => true]);

    expect(fn () => app(RemoveInstructor::class)->execute($course, $only))
        ->toThrow(ValidationException::class);
    expect($course->instructors()->count())->toBe(1);
});

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

/**
 * Create a course that already has a single assigned instructor.
 *
 * @return array{0: Course, 1: User}
 */
function courseWithInstructor(): array
{
    $course = Course::factory()->create();
    $instructor = userWithRole(UserRole::Instructor);
    $course->instructors()->attach($instructor, ['is_instructor' => true]);

    return [$course, $instructor];
}

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

it('lets an admin add an instructor', function () {
    [$course] = courseWithInstructor();
    $new_instructor = userWithRole(UserRole::Instructor);

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->post(route('courses.instructors.store', $course), [
            'user_ids' => [$new_instructor->id],
        ]);

    $response->assertRedirect(route('courses.show', $course));
    $response->assertSessionHas('success');
    expect($course->instructors()->whereKey($new_instructor->id)->exists())->toBeTrue();
});

it('lets an assigned instructor add another instructor', function () {
    [$course, $instructor] = courseWithInstructor();
    $new_instructor = userWithRole(UserRole::Instructor);

    $response = $this->actingAs($instructor)
        ->post(route('courses.instructors.store', $course), [
            'user_ids' => [$new_instructor->id],
        ]);

    $response->assertRedirect(route('courses.show', $course));
    expect($course->instructors()->count())->toBe(2);
});

it('forbids a non-manager from adding an instructor', function () {
    [$course] = courseWithInstructor();
    $eligible_target = userWithRole(UserRole::Instructor);
    $outsider = userWithRole(UserRole::Instructor);

    $response = $this->actingAs($outsider)
        ->post(route('courses.instructors.store', $course), [
            'user_ids' => [$eligible_target->id],
        ]);

    $response->assertForbidden();
    expect($course->instructors()->whereKey($eligible_target->id)->exists())->toBeFalse();
});

it('forbids a student from adding an instructor', function () {
    [$course] = courseWithInstructor();
    $eligible_target = userWithRole(UserRole::Instructor);

    $response = $this->actingAs(userWithRole(UserRole::Student))
        ->post(route('courses.instructors.store', $course), [
            'user_ids' => [$eligible_target->id],
        ]);

    $response->assertForbidden();
});

it('rejects adding a user without an instructor or admin role', function () {
    [$course] = courseWithInstructor();
    $student = userWithRole(UserRole::Student);

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->post(route('courses.instructors.store', $course), [
            'user_ids' => [$student->id],
        ]);

    $response->assertSessionHasErrors('user_ids.*');
    expect($course->instructors()->whereKey($student->id)->exists())->toBeFalse();
});

it('skips an already-assigned instructor without erroring', function () {
    [$course, $instructor] = courseWithInstructor();

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->post(route('courses.instructors.store', $course), [
            'user_ids' => [$instructor->id],
        ]);

    $response->assertRedirect(route('courses.show', $course));
    $response->assertSessionDoesntHaveErrors();
    expect($course->instructors()->count())->toBe(1);
});

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

it('lets an admin remove a non-last instructor', function () {
    [$course, $instructor] = courseWithInstructor();
    $second = userWithRole(UserRole::Instructor);
    $course->instructors()->attach($second, ['is_instructor' => true]);

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->delete(route('courses.instructors.destroy', ['course' => $course, 'user' => $second]));

    $response->assertRedirect(route('courses.show', $course));
    expect($course->instructors()->whereKey($second->id)->exists())->toBeFalse();
});

it('blocks removing the last instructor through the endpoint', function () {
    [$course, $instructor] = courseWithInstructor();

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->delete(route('courses.instructors.destroy', ['course' => $course, 'user' => $instructor]));

    $response->assertSessionHasErrors('user');
    expect($course->instructors()->count())->toBe(1);
});

it('forbids a non-manager from removing an instructor', function () {
    [$course, $instructor] = courseWithInstructor();
    $second = userWithRole(UserRole::Instructor);
    $course->instructors()->attach($second, ['is_instructor' => true]);

    $response = $this->actingAs(userWithRole(UserRole::Instructor))
        ->delete(route('courses.instructors.destroy', ['course' => $course, 'user' => $second]));

    $response->assertForbidden();
    expect($course->instructors()->count())->toBe(2);
});

it('searches assignable instructors for a manager, excluding the assigned and ineligible', function () {
    [$course, $assigned_instructor] = courseWithInstructor();
    $candidate = userWithRole(UserRole::Instructor);
    $student = userWithRole(UserRole::Student); // not eligible, must be excluded

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->getJson(route('courses.instructors.assignable', $course));

    $response->assertOk();
    $ids = collect($response->json())->pluck('id');
    expect($ids)->toContain($candidate->id)
        ->not->toContain($assigned_instructor->id)
        ->not->toContain($student->id);
});

it('filters assignable instructors by the search term', function () {
    [$course] = courseWithInstructor();
    $match = userWithRole(UserRole::Instructor);
    $match->update(['first_name' => 'Searchable', 'last_name' => 'Candidate']);
    userWithRole(UserRole::Instructor); // noise, should not match

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->getJson(route('courses.instructors.assignable', ['course' => $course, 'search' => 'Searchable']));

    $response->assertOk();
    $ids = collect($response->json())->pluck('id');
    expect($ids)->toContain($match->id)->toHaveCount(1);
});

it('forbids a non-manager from searching assignable instructors', function () {
    [$course] = courseWithInstructor();

    $this->actingAs(userWithRole(UserRole::Student))
        ->getJson(route('courses.instructors.assignable', $course))
        ->assertForbidden();
});
