<?php

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

it('hides non-published courses a student is enrolled in from the index', function () {
    $student = userWithRole(UserRole::Student);

    $published = Course::factory()->create(['status' => CourseStatus::Published]);
    $draft = Course::factory()->create(['status' => CourseStatus::Draft]);
    $archived = Course::factory()->create(['status' => CourseStatus::Archived]);

    foreach ([$published, $draft, $archived] as $course) {
        $course->students()->attach($student, ['is_instructor' => false]);
    }

    $this->actingAs($student)
        ->get(route('courses.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('courses.data', 1)
            ->where('courses.data.0.id', $published->id)
        );
});

it('forbids a student from viewing a non-published course they are enrolled in', function () {
    $student = userWithRole(UserRole::Student);
    $course = Course::factory()->create(['status' => CourseStatus::Draft]);
    $course->students()->attach($student, ['is_instructor' => false]);

    $this->actingAs($student)
        ->get(route('courses.show', $course))
        ->assertForbidden();
});

it('still shows non-published courses to their assigned instructor', function () {
    $instructor = userWithRole(UserRole::Instructor);
    $course = Course::factory()->create(['status' => CourseStatus::Draft]);
    $course->instructors()->attach($instructor, ['is_instructor' => true]);

    $this->actingAs($instructor)
        ->get(route('courses.show', $course))
        ->assertOk();
});

it('shows only published pages to a student on the course page', function () {
    $student = userWithRole(UserRole::Student);
    $course = Course::factory()->create(['status' => CourseStatus::Published]);
    $course->students()->attach($student, ['is_instructor' => false]);

    $published = Page::factory()->create([
        'course_id' => $course->id,
        'status' => CourseStatus::Published,
    ]);
    Page::factory()->create([
        'course_id' => $course->id,
        'status' => CourseStatus::Draft,
    ]);

    $this->actingAs($student)
        ->get(route('courses.show', $course))
        ->assertInertia(fn (Assert $page) => $page
            ->has('course.pages', 1)
            ->where('course.pages.0.id', $published->id)
            ->where('course.pages_count', 1)
        );
});

it('shows every page to a managing instructor on the course page', function () {
    $instructor = userWithRole(UserRole::Instructor);
    $course = Course::factory()->create(['status' => CourseStatus::Published]);
    $course->instructors()->attach($instructor, ['is_instructor' => true]);

    Page::factory()->create(['course_id' => $course->id, 'status' => CourseStatus::Published]);
    Page::factory()->create(['course_id' => $course->id, 'status' => CourseStatus::Draft]);

    $this->actingAs($instructor)
        ->get(route('courses.show', $course))
        ->assertInertia(fn (Assert $page) => $page
            ->has('course.pages', 2)
            ->where('course.pages_count', 2)
        );
});

it('forbids a student from directly viewing a non-published page', function () {
    $student = userWithRole(UserRole::Student);
    $course = Course::factory()->create(['status' => CourseStatus::Published]);
    $course->students()->attach($student, ['is_instructor' => false]);

    $page = Page::factory()->create([
        'course_id' => $course->id,
        'status' => CourseStatus::Draft,
    ]);

    $this->actingAs($student)
        ->get(route('pages.show', $page))
        ->assertForbidden();
});

it('allows a student to directly view a published page', function () {
    $student = userWithRole(UserRole::Student);
    $course = Course::factory()->create(['status' => CourseStatus::Published]);
    $course->students()->attach($student, ['is_instructor' => false]);

    $page = Page::factory()->create([
        'course_id' => $course->id,
        'status' => CourseStatus::Published,
    ]);

    $this->actingAs($student)
        ->get(route('pages.show', $page))
        ->assertOk();
});
