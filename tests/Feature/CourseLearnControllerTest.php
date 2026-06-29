<?php

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Page;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

function publishedCourseWithStudent(int $pageCount = 2): array
{
    $course = Course::factory()->published()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    $pages = collect(range(1, $pageCount))->map(
        fn (int $order) => Page::factory()->forCourse($course, $order)->create(['status' => CourseStatus::Published])
    );

    return [$course, $student, $pages];
}

it('renders the player for an enrolled student', function () {
    [$course, $student] = publishedCourseWithStudent();

    $this->actingAs($student)
        ->get(route('courses.learn', $course))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Learn')
            ->has('pages', 2)
            ->has('current_page')
            ->where('progress.total_count', 2)
        );
});

it('forbids a non-enrolled user from the player', function () {
    $course = Course::factory()->published()->create();
    $stranger = userWithRole(UserRole::Student);

    $this->actingAs($stranger)
        ->get(route('courses.learn', $course))
        ->assertForbidden();
});

it('forbids taking a non-published course', function () {
    $course = Course::factory()->create(['status' => CourseStatus::Draft]);
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    $this->actingAs($student)
        ->get(route('courses.learn', $course))
        ->assertForbidden();
});

it('completes a page and redirects to the next', function () {
    [$course, $student, $pages] = publishedCourseWithStudent();

    $this->actingAs($student)
        ->post(route('courses.learn.complete', [$course, $pages[0]]))
        ->assertRedirect(route('courses.learn.page', [$course, $pages[1]->id]));

    expect(UserProgress::where('user_id', $student->id)->where('page_id', $pages[0]->id)->exists())->toBeTrue();
});

it('redirects to the player with success after the final page', function () {
    [$course, $student, $pages] = publishedCourseWithStudent(1);

    $this->actingAs($student)
        ->post(route('courses.learn.complete', [$course, $pages[0]]))
        ->assertRedirect(route('courses.learn', $course))
        ->assertSessionHas('success');
});

it('forbids completing a locked page', function () {
    [$course, $student, $pages] = publishedCourseWithStudent();

    $this->actingAs($student)
        ->post(route('courses.learn.complete', [$course, $pages[1]]))
        ->assertForbidden();
});

it('attaches take and progress data to the course index for enrolled students', function () {
    [$course, $student, $pages] = publishedCourseWithStudent(2);
    UserProgress::create(['user_id' => $student->id, 'course_id' => $course->id, 'page_id' => $pages[0]->id]);

    $this->actingAs($student)
        ->get(route('courses.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->where('courses.data.0.can_take', true)
            ->where('courses.data.0.progress_percent', 50)
        );
});

it('index progress percent counts only published completed pages', function () {
    [$course, $student, $pages] = publishedCourseWithStudent(2);

    // Complete one published page.
    UserProgress::create(['user_id' => $student->id, 'course_id' => $course->id, 'page_id' => $pages[0]->id]);

    // Also record progress for a draft page (simulates a page completed then unpublished).
    $draft_page = Page::factory()->forCourse($course, 3)->create(['status' => CourseStatus::Draft]);
    UserProgress::create(['user_id' => $student->id, 'course_id' => $course->id, 'page_id' => $draft_page->id]);

    // 1 of 2 published pages completed = 50%, not 66% or 100%.
    $this->actingAs($student)
        ->get(route('courses.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->where('courses.data.0.progress_percent', 50)
        );
});
