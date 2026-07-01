<?php

use App\Enums\UserRole;
use App\Models\Course;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

it('shows a student only the courses they are assigned to', function () {
    $student = userWithRole(UserRole::Student);
    $assigned = Course::factory()->published()->create();
    $assigned->students()->attach($student, ['is_instructor' => false]);
    Course::factory()->create(); // unassigned course

    $this->actingAs($student)
        ->get(route('courses.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('courses.data', 1)
            ->where('courses.data.0.id', $assigned->id)
        );
});

it('shows an instructor only the courses they teach', function () {
    $instructor = userWithRole(UserRole::Instructor);
    $taught = Course::factory()->create();
    $taught->instructors()->attach($instructor, ['is_instructor' => true]);
    Course::factory()->create(); // course taught by someone else

    $this->actingAs($instructor)
        ->get(route('courses.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('courses.data', 1)
            ->where('courses.data.0.id', $taught->id)
        );
});

it('shows an admin every course', function () {
    $admin = userWithRole(UserRole::Admin);
    Course::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('courses.index'))
        ->assertInertia(fn (Assert $page) => $page->has('courses.data', 3));
});

it('excludes courses whose enrollment was soft deleted', function () {
    $student = userWithRole(UserRole::Student);
    $course = Course::factory()->create();
    $course->students()->attach($student, ['is_instructor' => false]);
    DB::table('courses_users')
        ->where('user_id', $student->id)
        ->update(['deleted_at' => now()]);

    $this->actingAs($student)
        ->get(route('courses.index'))
        ->assertInertia(fn (Assert $page) => $page->has('courses.data', 0));
});

it('lets a student open a course they are assigned to', function () {
    $student = userWithRole(UserRole::Student);
    $course = Course::factory()->published()->create();
    $course->students()->attach($student, ['is_instructor' => false]);

    $this->actingAs($student)
        ->get(route('courses.show', $course))
        ->assertOk();
});

it('forbids a student from opening a course they are not assigned to', function () {
    $student = userWithRole(UserRole::Student);
    $course = Course::factory()->create();

    $this->actingAs($student)
        ->get(route('courses.show', $course))
        ->assertForbidden();
});

it('lets an admin open any course', function () {
    $admin = userWithRole(UserRole::Admin);
    $course = Course::factory()->create();

    $this->actingAs($admin)
        ->get(route('courses.show', $course))
        ->assertOk();
});

it('forbids a student from opening a course whose enrollment was soft deleted', function () {
    $student = userWithRole(UserRole::Student);
    $course = Course::factory()->create();
    $course->students()->attach($student, ['is_instructor' => false]);
    DB::table('courses_users')->where('user_id', $student->id)->update(['deleted_at' => now()]);

    $this->actingAs($student)
        ->get(route('courses.show', $course))
        ->assertForbidden();
});

it('forbids an instructor from opening a course they do not teach', function () {
    $instructor = userWithRole(UserRole::Instructor);
    $course = Course::factory()->create();

    $this->actingAs($instructor)
        ->get(route('courses.show', $course))
        ->assertForbidden();
});
