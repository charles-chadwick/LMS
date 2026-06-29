<?php

use App\Actions\Courses\LoadCoursePlayer;
use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Page;
use App\Models\UserProgress;
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

it('builds player state with locked, completed flags and percent', function () {
    $course = Course::factory()->published()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    $page1 = Page::factory()->forCourse($course, 1)->create(['status' => CourseStatus::Published]);
    $page2 = Page::factory()->forCourse($course, 2)->create(['status' => CourseStatus::Published]);
    $page3 = Page::factory()->forCourse($course, 3)->create(['status' => CourseStatus::Published]);
    Page::factory()->forCourse($course, 4)->create(['status' => CourseStatus::Draft]);

    UserProgress::create(['user_id' => $student->id, 'course_id' => $course->id, 'page_id' => $page1->id]);

    $state = app(LoadCoursePlayer::class)->execute($course, $student);

    expect($state['pages'])->toHaveCount(3)
        ->and($state['progress'])->toMatchArray(['completed_count' => 1, 'total_count' => 3, 'percent' => 33])
        ->and($state['is_complete'])->toBeFalse()
        ->and($state['pages'][0])->toMatchArray(['id' => $page1->id, 'is_completed' => true, 'is_locked' => false])
        ->and($state['pages'][1])->toMatchArray(['id' => $page2->id, 'is_completed' => false, 'is_locked' => false])
        ->and($state['pages'][2])->toMatchArray(['id' => $page3->id, 'is_completed' => false, 'is_locked' => true])
        ->and($state['current_page']['id'])->toBe($page2->id);
});

it('reports completion when every published page is done', function () {
    $course = Course::factory()->published()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false, 'completed_at' => now()]);

    $page1 = Page::factory()->forCourse($course, 1)->create(['status' => CourseStatus::Published]);
    $page2 = Page::factory()->forCourse($course, 2)->create(['status' => CourseStatus::Published]);
    foreach ([$page1, $page2] as $page) {
        UserProgress::create(['user_id' => $student->id, 'course_id' => $course->id, 'page_id' => $page->id]);
    }

    $state = app(LoadCoursePlayer::class)->execute($course, $student);

    expect($state['is_complete'])->toBeTrue()
        ->and($state['progress']['percent'])->toBe(100)
        ->and($state['completed_at'])->not->toBeNull()
        ->and($state['current_page']['id'])->toBe($page2->id);
});

it('falls back to first incomplete page when a locked page is requested', function () {
    $course = Course::factory()->published()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    $page1 = Page::factory()->forCourse($course, 1)->create(['status' => CourseStatus::Published]);
    $page2 = Page::factory()->forCourse($course, 2)->create(['status' => CourseStatus::Published]);

    $state = app(LoadCoursePlayer::class)->execute($course, $student, $page2);

    expect($state['current_page']['id'])->toBe($page1->id);
});
