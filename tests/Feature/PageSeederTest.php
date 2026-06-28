<?php

use App\Models\Course;
use App\Models\Page;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('seeds each existing course with sequentially ordered pages', function () {
    $course = Course::factory()->create();

    $this->seed(PageSeeder::class);

    $pages = Page::where('course_id', $course->id)->orderBy('order')->get();

    expect($pages)->not->toBeEmpty()
        ->and($pages->pluck('order')->all())
        ->toBe(range(1, $pages->count()));
});

it('creates courses when none exist before seeding pages', function () {
    $this->seed(PageSeeder::class);

    expect(Course::count())->toBeGreaterThan(0)
        ->and(Page::count())->toBeGreaterThan(0);
});
