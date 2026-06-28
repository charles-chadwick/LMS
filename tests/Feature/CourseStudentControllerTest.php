<?php

use App\Actions\Courses\AssignStudent;
use App\Actions\Courses\RemoveStudent;
use App\Models\Course;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

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
