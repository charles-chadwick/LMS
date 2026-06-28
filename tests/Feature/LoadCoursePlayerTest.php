<?php

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Page;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('published scope returns only published pages', function () {
    $course = Course::factory()->create();
    Page::factory()->forCourse($course, 1)->create(['status' => CourseStatus::Published]);
    Page::factory()->forCourse($course, 2)->create(['status' => CourseStatus::Draft]);
    Page::factory()->forCourse($course, 3)->create(['status' => CourseStatus::Archived]);

    $published = $course->pages()->published()->get();

    expect($published)->toHaveCount(1)
        ->and($published->first()->status)->toBe(CourseStatus::Published);
});
