<?php

use App\Actions\Courses\AssignInstructor;
use App\Actions\Courses\RemoveInstructor;
use App\Enums\CourseStatus;
use App\Models\Course;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

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
