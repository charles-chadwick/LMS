<?php

use App\Enums\CourseStatus;
use App\Models\Course;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

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
