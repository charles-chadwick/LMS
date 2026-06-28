<?php

use App\Actions\Courses\ListAssignableStudents;
use App\Models\Course;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('lists Student-role users excluding enrolled students and course instructors', function () {
    $course = Course::factory()->create();

    $candidate = userWithRole('Student');
    $enrolled_student = userWithRole('Student');
    $course->students()->attach($enrolled_student, ['is_instructor' => false]);

    $course_instructor = userWithRole('Student'); // a student who also instructs this course
    $course->instructors()->attach($course_instructor, ['is_instructor' => true]);

    $non_student = userWithRole('Instructor');

    $assignable = app(ListAssignableStudents::class)->execute($course);
    $ids = $assignable->pluck('id');

    expect($ids)->toContain($candidate->id)
        ->and($ids)->not->toContain($enrolled_student->id)
        ->and($ids)->not->toContain($course_instructor->id)
        ->and($ids)->not->toContain($non_student->id);
});
