<?php

use App\Actions\Courses\AssignStudent;
use App\Actions\Courses\RemoveStudent;
use App\Models\Course;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

it('authorizes admins and assigned instructors to manage students', function () {
    $course = Course::factory()->create();
    $instructor = userWithRole('Instructor');
    $course->instructors()->attach($instructor, ['is_instructor' => true]);

    $admin = userWithRole('Admin');
    $other_instructor = userWithRole('Instructor');
    $student = userWithRole('Student');

    expect($admin->can('manageStudents', $course))->toBeTrue()
        ->and($instructor->can('manageStudents', $course))->toBeTrue()
        ->and($other_instructor->can('manageStudents', $course))->toBeFalse()
        ->and($student->can('manageStudents', $course))->toBeFalse();
});

it('attaches a user as a student via the AssignStudent action', function () {
    $course = Course::factory()->create();
    $user = userWithRole('Student');

    app(AssignStudent::class)->execute($course, $user);

    expect($course->students()->whereKey($user->id)->exists())->toBeTrue();
});

it('detaches a student via the RemoveStudent action', function () {
    $course = Course::factory()->create();
    $student = userWithRole('Student');
    $course->students()->attach($student, ['is_instructor' => false]);

    app(RemoveStudent::class)->execute($course, $student);

    expect($course->students()->whereKey($student->id)->exists())->toBeFalse();
});

it('removes the last student without error', function () {
    $course = Course::factory()->create();
    $student = userWithRole('Student');
    $course->students()->attach($student, ['is_instructor' => false]);

    app(RemoveStudent::class)->execute($course, $student);

    expect($course->students()->count())->toBe(0);
});

it('lets an admin enroll a student', function () {
    [$course] = courseWithManager();
    $student = userWithRole('Student');

    $response = $this->actingAs(userWithRole('Admin'))
        ->post(route('courses.students.store', $course), ['user_id' => $student->id]);

    $response->assertRedirect(route('courses.show', $course));
    $response->assertSessionHas('success');
    expect($course->students()->whereKey($student->id)->exists())->toBeTrue();
});

it('lets an assigned instructor enroll a student', function () {
    [$course, $instructor] = courseWithManager();
    $student = userWithRole('Student');

    $response = $this->actingAs($instructor)
        ->post(route('courses.students.store', $course), ['user_id' => $student->id]);

    $response->assertRedirect(route('courses.show', $course));
    expect($course->students()->count())->toBe(1);
});

it('forbids a non-manager from enrolling a student', function () {
    [$course] = courseWithManager();
    $student = userWithRole('Student');

    $response = $this->actingAs(userWithRole('Instructor'))
        ->post(route('courses.students.store', $course), ['user_id' => $student->id]);

    $response->assertForbidden();
    expect($course->students()->whereKey($student->id)->exists())->toBeFalse();
});

it('rejects enrolling a user without the Student role', function () {
    [$course] = courseWithManager();
    $non_student = userWithRole('Instructor');

    $response = $this->actingAs(userWithRole('Admin'))
        ->post(route('courses.students.store', $course), ['user_id' => $non_student->id]);

    $response->assertSessionHasErrors('user_id');
    expect($course->students()->whereKey($non_student->id)->exists())->toBeFalse();
});

it('rejects enrolling an already-enrolled student', function () {
    [$course] = courseWithManager();
    $student = userWithRole('Student');
    $course->students()->attach($student, ['is_instructor' => false]);

    $response = $this->actingAs(userWithRole('Admin'))
        ->post(route('courses.students.store', $course), ['user_id' => $student->id]);

    $response->assertSessionHasErrors('user_id');
    expect($course->students()->count())->toBe(1);
});

it('lets an admin remove a student', function () {
    [$course] = courseWithManager();
    $student = userWithRole('Student');
    $course->students()->attach($student, ['is_instructor' => false]);

    $response = $this->actingAs(userWithRole('Admin'))
        ->delete(route('courses.students.destroy', ['course' => $course, 'user' => $student]));

    $response->assertRedirect(route('courses.show', $course));
    expect($course->students()->whereKey($student->id)->exists())->toBeFalse();
});

it('forbids a non-manager from removing a student', function () {
    [$course] = courseWithManager();
    $student = userWithRole('Student');
    $course->students()->attach($student, ['is_instructor' => false]);

    $response = $this->actingAs(userWithRole('Instructor'))
        ->delete(route('courses.students.destroy', ['course' => $course, 'user' => $student]));

    $response->assertForbidden();
    expect($course->students()->whereKey($student->id)->exists())->toBeTrue();
});

it('exposes assignable students and the manage flag to a manager', function () {
    [$course] = courseWithManager();
    $candidate = userWithRole('Student');
    $enrolled = userWithRole('Student');
    $course->students()->attach($enrolled, ['is_instructor' => false]);

    $response = $this->actingAs(userWithRole('Admin'))
        ->get(route('courses.show', $course));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('can.manage_students', true)
        ->where('assignable_students', fn ($candidates) => collect($candidates)->contains('id', $candidate->id)
            && ! collect($candidates)->contains('id', $enrolled->id))
    );
});

it('hides assignable students from a non-manager', function () {
    [$course] = courseWithManager();
    userWithRole('Student');

    $response = $this->actingAs(userWithRole('Student'))
        ->get(route('courses.show', $course));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('can.manage_students', false)
        ->where('assignable_students', [])
    );
});

it('rejects enrolling a user who instructs the course', function () {
    [$course] = courseWithManager();
    $dual_role = userWithRole('Student');
    $dual_role->assignRole('Instructor');
    $course->instructors()->attach($dual_role, ['is_instructor' => true]);

    $response = $this->actingAs(userWithRole('Admin'))
        ->post(route('courses.students.store', $course), ['user_id' => $dual_role->id]);

    $response->assertSessionHasErrors('user_id');
    expect($course->students()->whereKey($dual_role->id)->exists())->toBeFalse();
});

/**
 * Create a course that already has one assigned instructor.
 *
 * @return array{0: Course, 1: User}
 */
function courseWithManager(): array
{
    $course = Course::factory()->create();
    $instructor = userWithRole('Instructor');
    $course->instructors()->attach($instructor, ['is_instructor' => true]);

    return [$course, $instructor];
}
