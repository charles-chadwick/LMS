<?php

use App\Enums\UserRole;
use App\Models\Course;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

it('forbids viewing the certificate before completion', function () {
    $course = Course::factory()->published()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    $this->actingAs($student)
        ->get(route('courses.certificate', $course))
        ->assertForbidden();
});

it('renders the certificate after completion', function () {
    $course = Course::factory()->published()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false, 'completed_at' => now()]);

    $this->actingAs($student)
        ->get(route('courses.certificate', $course))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Certificate')
            ->where('course.id', $course->id)
            ->where('student.id', $student->id)
            ->has('completed_at')
        );
});
