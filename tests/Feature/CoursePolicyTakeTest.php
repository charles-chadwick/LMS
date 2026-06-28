<?php

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Course;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('allows an enrolled student to take a published course', function () {
    $course = Course::factory()->published()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    expect($student->can('take', $course))->toBeTrue();
});

it('forbids taking a non-published course even when enrolled', function () {
    $course = Course::factory()->create(['status' => CourseStatus::Draft]);
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    expect($student->can('take', $course))->toBeFalse();
});

it('forbids a non-enrolled user from taking a course', function () {
    $course = Course::factory()->published()->create();
    $student = userWithRole(UserRole::Student);

    expect($student->can('take', $course))->toBeFalse();
});

it('forbids an instructor of the course from taking it', function () {
    $course = Course::factory()->published()->create();
    $instructor = userWithRole(UserRole::Instructor);
    $course->instructors()->attach($instructor, ['is_instructor' => true]);

    expect($instructor->can('take', $course))->toBeFalse();
});

it('only allows viewing a certificate once completed_at is set', function () {
    $course = Course::factory()->published()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    expect($student->can('viewCertificate', $course))->toBeFalse();

    $course->students()->updateExistingPivot($student->id, ['completed_at' => now()]);

    expect($student->fresh()->can('viewCertificate', $course->fresh()))->toBeTrue();
});
