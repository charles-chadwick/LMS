<?php

use App\Enums\UserRole;
use App\Models\Course;
use Database\Seeders\CourseTableSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

it('attaches a generated cover to each existing course', function () {
    Storage::fake('public');

    $course = Course::factory()->create();

    $this->seed(CourseTableSeeder::class);

    expect($course->fresh()->cover)->toBeArray()
        ->and($course->fresh()->getFirstMedia('cover'))->not->toBeNull();
});

it('creates covered courses when none exist before seeding', function () {
    Storage::fake('public');

    $this->seed(CourseTableSeeder::class);

    expect(Course::count())->toBeGreaterThan(0)
        ->and(Course::get()->every(fn (Course $course) => $course->cover !== null))->toBeTrue();
});

it('does not replace a course that already has a cover', function () {
    Storage::fake('public');

    $course = Course::factory()->withCover()->create();
    $original_media_id = $course->getFirstMedia('cover')->id;

    $this->seed(CourseTableSeeder::class);

    expect($course->fresh()->getFirstMedia('cover')->id)->toBe($original_media_id);
});

it('enrolls a random roster of instructors and students within bounds', function () {
    Storage::fake('public');

    userWithRole(UserRole::Instructor);
    userWithRole(UserRole::Instructor);
    userWithRole(UserRole::Instructor);
    userWithRole(UserRole::Instructor);
    userWithRole(UserRole::Instructor);
    collect(range(1, 120))->each(fn () => userWithRole(UserRole::Student));

    $course = Course::factory()->create();

    $this->seed(CourseTableSeeder::class);

    $course->loadCount(['instructors', 'students']);

    expect($course->instructors_count)->toBeGreaterThanOrEqual(1)
        ->and($course->instructors_count)->toBeLessThanOrEqual(5)
        ->and($course->students_count)->toBeGreaterThanOrEqual(0)
        ->and($course->students_count)->toBeLessThanOrEqual(100);
});

it('does not re-enroll a course that already has members', function () {
    Storage::fake('public');

    $instructor = userWithRole(UserRole::Instructor);
    userWithRole(UserRole::Student);

    $course = Course::factory()->create();
    $course->instructors()->attach($instructor, ['is_instructor' => true]);

    $this->seed(CourseTableSeeder::class);

    $course->loadCount('users');

    expect($course->users_count)->toBe(1);
});
