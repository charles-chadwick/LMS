<?php

use App\Actions\Courses\CompletePage;
use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Page;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

uses(LazilyRefreshDatabase::class);

function enrolledCourse(): array
{
    $course = Course::factory()->published()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    return [$course, $student];
}

it('records progress and returns the next incomplete page id', function () {
    [$course, $student] = enrolledCourse();
    $page1 = Page::factory()->forCourse($course, 1)->create(['status' => CourseStatus::Published]);
    $page2 = Page::factory()->forCourse($course, 2)->create(['status' => CourseStatus::Published]);

    $next = app(CompletePage::class)->execute($student, $course, $page1);

    expect($next)->toBe($page2->id)
        ->and(UserProgress::where('user_id', $student->id)->where('page_id', $page1->id)->exists())->toBeTrue();
});

it('records progress idempotently without duplicate rows', function () {
    [$course, $student] = enrolledCourse();
    $page1 = Page::factory()->forCourse($course, 1)->create(['status' => CourseStatus::Published]);

    $action = app(CompletePage::class);
    $action->execute($student, $course, $page1);
    $action->execute($student, $course, $page1);

    expect(UserProgress::where('user_id', $student->id)->where('page_id', $page1->id)->count())->toBe(1);
});

it('stamps completed_at when the final published page is completed', function () {
    [$course, $student] = enrolledCourse();
    $page1 = Page::factory()->forCourse($course, 1)->create(['status' => CourseStatus::Published]);
    $page2 = Page::factory()->forCourse($course, 2)->create(['status' => CourseStatus::Published]);

    $action = app(CompletePage::class);
    $action->execute($student, $course, $page1);
    expect($course->students()->whereKey($student->id)->first()->pivot->completed_at)->toBeNull();

    $next = $action->execute($student, $course, $page2);

    expect($next)->toBeNull()
        ->and($course->students()->whereKey($student->id)->first()->pivot->completed_at)->not->toBeNull();
});

it('aborts when an earlier published page is not yet complete', function () {
    [$course, $student] = enrolledCourse();
    Page::factory()->forCourse($course, 1)->create(['status' => CourseStatus::Published]);
    $page2 = Page::factory()->forCourse($course, 2)->create(['status' => CourseStatus::Published]);

    app(CompletePage::class)->execute($student, $course, $page2);
})->throws(HttpException::class);

it('aborts when the page is not a published page of the course', function () {
    [$course, $student] = enrolledCourse();
    $draft = Page::factory()->forCourse($course, 1)->create(['status' => CourseStatus::Draft]);

    app(CompletePage::class)->execute($student, $course, $draft);
})->throws(NotFoundHttpException::class);
