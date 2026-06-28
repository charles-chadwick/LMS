<?php

use App\Actions\Courses\AssignStudent;
use App\Actions\Courses\RemoveStudent;
use App\Enums\UserRole;
use App\Models\Course;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

it('authorizes admins and assigned instructors to manage students', function () {
    $course = Course::factory()->create();
    $instructor = userWithRole(UserRole::Instructor);
    $course->instructors()->attach($instructor, ['is_instructor' => true]);

    $admin = userWithRole(UserRole::Admin);
    $other_instructor = userWithRole(UserRole::Instructor);
    $student = userWithRole(UserRole::Student);

    expect($admin->can('manageStudents', $course))->toBeTrue()
        ->and($instructor->can('manageStudents', $course))->toBeTrue()
        ->and($other_instructor->can('manageStudents', $course))->toBeFalse()
        ->and($student->can('manageStudents', $course))->toBeFalse();
});

it('attaches a user as a student via the AssignStudent action', function () {
    $course = Course::factory()->create();
    $user = userWithRole(UserRole::Student);

    app(AssignStudent::class)->execute($course, $user);

    expect($course->students()->whereKey($user->id)->exists())->toBeTrue();
});

it('detaches a student via the RemoveStudent action', function () {
    $course = Course::factory()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    app(RemoveStudent::class)->execute($course, $student);

    expect($course->students()->whereKey($student->id)->exists())->toBeFalse();
});

it('removes the last student without error', function () {
    $course = Course::factory()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    app(RemoveStudent::class)->execute($course, $student);

    expect($course->students()->count())->toBe(0);
});

it('lets an admin enroll a student', function () {
    [$course] = courseWithManager();
    $student = userWithRole(UserRole::Student);

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->post(route('courses.students.store', $course), ['user_id' => $student->id]);

    $response->assertRedirect(route('courses.show', $course));
    $response->assertSessionHas('success');
    expect($course->students()->whereKey($student->id)->exists())->toBeTrue();
});

it('lets an assigned instructor enroll a student', function () {
    [$course, $instructor] = courseWithManager();
    $student = userWithRole(UserRole::Student);

    $response = $this->actingAs($instructor)
        ->post(route('courses.students.store', $course), ['user_id' => $student->id]);

    $response->assertRedirect(route('courses.show', $course));
    expect($course->students()->count())->toBe(1);
});

it('forbids a non-manager from enrolling a student', function () {
    [$course] = courseWithManager();
    $student = userWithRole(UserRole::Student);

    $response = $this->actingAs(userWithRole(UserRole::Instructor))
        ->post(route('courses.students.store', $course), ['user_id' => $student->id]);

    $response->assertForbidden();
    expect($course->students()->whereKey($student->id)->exists())->toBeFalse();
});

it('rejects enrolling a user without the Student role', function () {
    [$course] = courseWithManager();
    $non_student = userWithRole(UserRole::Instructor);

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->post(route('courses.students.store', $course), ['user_id' => $non_student->id]);

    $response->assertSessionHasErrors('user_id');
    expect($course->students()->whereKey($non_student->id)->exists())->toBeFalse();
});

it('rejects enrolling an already-enrolled student', function () {
    [$course] = courseWithManager();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->post(route('courses.students.store', $course), ['user_id' => $student->id]);

    $response->assertSessionHasErrors('user_id');
    expect($course->students()->count())->toBe(1);
});

it('lets an admin remove a student', function () {
    [$course] = courseWithManager();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->delete(route('courses.students.destroy', ['course' => $course, 'user' => $student]));

    $response->assertRedirect(route('courses.show', $course));
    expect($course->students()->whereKey($student->id)->exists())->toBeFalse();
});

it('forbids a non-manager from removing a student', function () {
    [$course] = courseWithManager();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    $response = $this->actingAs(userWithRole(UserRole::Instructor))
        ->delete(route('courses.students.destroy', ['course' => $course, 'user' => $student]));

    $response->assertForbidden();
    expect($course->students()->whereKey($student->id)->exists())->toBeTrue();
});

it('exposes assignable students and the manage flag to a manager', function () {
    [$course] = courseWithManager();
    $candidate = userWithRole(UserRole::Student);
    $enrolled = userWithRole(UserRole::Student);
    $course->students()->attach($enrolled, ['is_instructor' => false]);

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->get(route('courses.show', $course));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('can.manage_students', true)
        ->where('assignable_students', fn ($candidates) => collect($candidates)->contains('id', $candidate->id)
            && ! collect($candidates)->contains('id', $enrolled->id))
    );
});

it('hides assignable students from a non-manager', function () {
    [$course] = courseWithManager();
    userWithRole(UserRole::Student);

    $response = $this->actingAs(userWithRole(UserRole::Student))
        ->get(route('courses.show', $course));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('can.manage_students', false)
        ->where('assignable_students', [])
    );
});

it('rejects enrolling a user who instructs the course', function () {
    [$course] = courseWithManager();
    $dual_role = userWithRole(UserRole::Student);
    $dual_role->assignRole(UserRole::Instructor);
    $course->instructors()->attach($dual_role, ['is_instructor' => true]);

    $response = $this->actingAs(userWithRole(UserRole::Admin))
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
    $instructor = userWithRole(UserRole::Instructor);
    $course->instructors()->attach($instructor, ['is_instructor' => true]);

    return [$course, $instructor];
}
