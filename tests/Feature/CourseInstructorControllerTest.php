<?php

use App\Actions\Courses\AssignInstructor;
use App\Actions\Courses\RemoveInstructor;
use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

it('assigns the creator as an instructor when a course is created', function () {
    $creator = userWithRole('Instructor');

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
    $instructor = userWithRole('Instructor');
    $course->instructors()->attach($instructor, ['is_instructor' => true]);

    $admin = userWithRole('Admin');
    $other_instructor = userWithRole('Instructor');
    $student = userWithRole('Student');

    expect($admin->can('manageInstructors', $course))->toBeTrue()
        ->and($instructor->can('manageInstructors', $course))->toBeTrue()
        ->and($other_instructor->can('manageInstructors', $course))->toBeFalse()
        ->and($student->can('manageInstructors', $course))->toBeFalse();
});

it('attaches a user as an instructor via the AssignInstructor action', function () {
    $course = Course::factory()->create();
    $user = userWithRole('Instructor');

    app(AssignInstructor::class)->execute($course, $user);

    expect($course->instructors()->whereKey($user->id)->exists())->toBeTrue();
});

it('detaches a non-last instructor via the RemoveInstructor action', function () {
    $course = Course::factory()->create();
    $keep = userWithRole('Instructor');
    $remove = userWithRole('Instructor');
    $course->instructors()->attach($keep, ['is_instructor' => true]);
    $course->instructors()->attach($remove, ['is_instructor' => true]);

    app(RemoveInstructor::class)->execute($course, $remove);

    expect($course->instructors()->whereKey($remove->id)->exists())->toBeFalse()
        ->and($course->instructors()->count())->toBe(1);
});

it('refuses to remove the last instructor', function () {
    $course = Course::factory()->create();
    $only = userWithRole('Instructor');
    $course->instructors()->attach($only, ['is_instructor' => true]);

    expect(fn () => app(RemoveInstructor::class)->execute($course, $only))
        ->toThrow(ValidationException::class);
    expect($course->instructors()->count())->toBe(1);
});

/**
 * Create a course that already has a single assigned instructor.
 *
 * @return array{0: Course, 1: User}
 */
function courseWithInstructor(): array
{
    $course = Course::factory()->create();
    $instructor = userWithRole('Instructor');
    $course->instructors()->attach($instructor, ['is_instructor' => true]);

    return [$course, $instructor];
}

it('lets an admin add an instructor', function () {
    [$course] = courseWithInstructor();
    $new_instructor = userWithRole('Instructor');

    $response = $this->actingAs(userWithRole('Admin'))
        ->post(route('courses.instructors.store', $course), [
            'user_id' => $new_instructor->id,
        ]);

    $response->assertRedirect(route('courses.show', $course));
    $response->assertSessionHas('success');
    expect($course->instructors()->whereKey($new_instructor->id)->exists())->toBeTrue();
});

it('lets an assigned instructor add another instructor', function () {
    [$course, $instructor] = courseWithInstructor();
    $new_instructor = userWithRole('Instructor');

    $response = $this->actingAs($instructor)
        ->post(route('courses.instructors.store', $course), [
            'user_id' => $new_instructor->id,
        ]);

    $response->assertRedirect(route('courses.show', $course));
    expect($course->instructors()->count())->toBe(2);
});

it('forbids a non-manager from adding an instructor', function () {
    [$course] = courseWithInstructor();
    $eligible_target = userWithRole('Instructor');
    $outsider = userWithRole('Instructor');

    $response = $this->actingAs($outsider)
        ->post(route('courses.instructors.store', $course), [
            'user_id' => $eligible_target->id,
        ]);

    $response->assertForbidden();
    expect($course->instructors()->whereKey($eligible_target->id)->exists())->toBeFalse();
});

it('forbids a student from adding an instructor', function () {
    [$course] = courseWithInstructor();
    $eligible_target = userWithRole('Instructor');

    $response = $this->actingAs(userWithRole('Student'))
        ->post(route('courses.instructors.store', $course), [
            'user_id' => $eligible_target->id,
        ]);

    $response->assertForbidden();
});

it('rejects adding a user without an instructor or admin role', function () {
    [$course] = courseWithInstructor();
    $student = userWithRole('Student');

    $response = $this->actingAs(userWithRole('Admin'))
        ->post(route('courses.instructors.store', $course), [
            'user_id' => $student->id,
        ]);

    $response->assertSessionHasErrors('user_id');
    expect($course->instructors()->whereKey($student->id)->exists())->toBeFalse();
});

it('rejects adding an already-assigned instructor', function () {
    [$course, $instructor] = courseWithInstructor();

    $response = $this->actingAs(userWithRole('Admin'))
        ->post(route('courses.instructors.store', $course), [
            'user_id' => $instructor->id,
        ]);

    $response->assertSessionHasErrors('user_id');
    expect($course->instructors()->count())->toBe(1);
});

it('lets an admin remove a non-last instructor', function () {
    [$course, $instructor] = courseWithInstructor();
    $second = userWithRole('Instructor');
    $course->instructors()->attach($second, ['is_instructor' => true]);

    $response = $this->actingAs(userWithRole('Admin'))
        ->delete(route('courses.instructors.destroy', ['course' => $course, 'user' => $second]));

    $response->assertRedirect(route('courses.show', $course));
    expect($course->instructors()->whereKey($second->id)->exists())->toBeFalse();
});

it('blocks removing the last instructor through the endpoint', function () {
    [$course, $instructor] = courseWithInstructor();

    $response = $this->actingAs(userWithRole('Admin'))
        ->delete(route('courses.instructors.destroy', ['course' => $course, 'user' => $instructor]));

    $response->assertSessionHasErrors('user');
    expect($course->instructors()->count())->toBe(1);
});

it('forbids a non-manager from removing an instructor', function () {
    [$course, $instructor] = courseWithInstructor();
    $second = userWithRole('Instructor');
    $course->instructors()->attach($second, ['is_instructor' => true]);

    $response = $this->actingAs(userWithRole('Instructor'))
        ->delete(route('courses.instructors.destroy', ['course' => $course, 'user' => $second]));

    $response->assertForbidden();
    expect($course->instructors()->count())->toBe(2);
});

it('exposes assignable instructors and the manage flag to a manager', function () {
    [$course] = courseWithInstructor();
    $candidate = userWithRole('Instructor');
    userWithRole('Student'); // not eligible, must be excluded

    $response = $this->actingAs(userWithRole('Admin'))
        ->get(route('courses.show', $course));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('can.manage_instructors', true)
        ->where('assignable_instructors', fn ($candidates) => collect($candidates)->contains('id', $candidate->id)
            && collect($candidates)->count() >= 1)
    );
});

it('hides assignable instructors from a non-manager', function () {
    [$course] = courseWithInstructor();
    userWithRole('Instructor');

    $response = $this->actingAs(userWithRole('Student'))
        ->get(route('courses.show', $course));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('can.manage_instructors', false)
        ->where('assignable_instructors', [])
    );
});
