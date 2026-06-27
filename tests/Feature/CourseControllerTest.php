<?php

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

it('lists courses with relationship counts on the index', function () {
    $course = Course::factory()->create();
    $course->students()->attach(User::factory()->count(2)->create(), ['is_instructor' => false]);
    $course->instructors()->attach(User::factory()->create(), ['is_instructor' => true]);
    Page::factory()->count(3)->create(['course_id' => $course->id]);

    $response = $this->get(route('courses.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Courses/Index')
        ->has('courses.data', 1)
        ->where('courses.data.0.pages_count', 3)
        ->where('courses.data.0.students_count', 2)
        ->where('courses.data.0.instructors_count', 1)
    );
});

it('filters courses by search term', function () {
    Course::factory()->create(['title' => 'Introduction to Biology']);
    Course::factory()->create(['title' => 'Advanced Chemistry']);

    $response = $this->get(route('courses.index', ['search' => 'Biology']));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('courses.data', 1)
        ->where('courses.data.0.title', 'Introduction to Biology')
    );
});

it('filters courses by status', function () {
    Course::factory()->create(['status' => CourseStatus::Published]);
    Course::factory()->create(['status' => CourseStatus::Draft]);

    $response = $this->get(route('courses.index', ['status' => CourseStatus::Published->value]));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('courses.data', 1)
        ->where('courses.data.0.status', CourseStatus::Published->value)
    );
});

it('creates a course', function () {
    $response = $this->post(route('courses.store'), [
        'status' => CourseStatus::Draft->value,
        'title' => 'New Course',
        'code' => 'NEW-101',
    ]);

    $course = Course::firstWhere('code', 'NEW-101');

    expect($course)->not->toBeNull()
        ->and($course->title)->toBe('New Course');
    $response->assertRedirect(route('courses.show', $course));
    $response->assertSessionHas('success');
});

it('validates required fields when creating a course', function () {
    $response = $this->post(route('courses.store'), []);

    $response->assertSessionHasErrors(['status', 'title', 'code']);
    expect(Course::count())->toBe(0);
});

it('rejects a duplicate code when creating a course', function () {
    Course::factory()->create(['code' => 'DUP-101']);

    $response = $this->post(route('courses.store'), [
        'status' => CourseStatus::Draft->value,
        'title' => 'Another Course',
        'code' => 'DUP-101',
    ]);

    $response->assertSessionHasErrors('code');
    expect(Course::count())->toBe(1);
});

it('displays a course with loaded relationships', function () {
    $course = Course::factory()->create();
    $student = User::factory()->create();
    $instructor = User::factory()->create();
    $course->students()->attach($student, ['is_instructor' => false]);
    $course->instructors()->attach($instructor, ['is_instructor' => true]);
    Page::factory()->create(['course_id' => $course->id]);

    $response = $this->get(route('courses.show', $course));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Courses/Show')
        ->where('course.id', $course->id)
        ->has('course.pages', 1)
        ->has('course.students', 1)
        ->has('course.instructors', 1)
        ->where('course.students.0.id', $student->id)
        ->where('course.pages_count', 1)
    );
});

it('updates a course', function () {
    $course = Course::factory()->create(['title' => 'Old Title']);

    $response = $this->put(route('courses.update', $course), [
        'status' => CourseStatus::Published->value,
        'title' => 'Updated Title',
        'code' => $course->code,
    ]);

    $response->assertRedirect(route('courses.show', $course));
    expect($course->fresh())
        ->title->toBe('Updated Title')
        ->status->toBe(CourseStatus::Published->value);
});

it('validates required fields when updating a course', function () {
    $course = Course::factory()->create(['title' => 'Keep Me']);

    $response = $this->put(route('courses.update', $course), [
        'status' => '',
        'title' => '',
        'code' => '',
    ]);

    $response->assertSessionHasErrors(['status', 'title', 'code']);
    expect($course->fresh()->title)->toBe('Keep Me');
});

it('soft deletes a course', function () {
    $course = Course::factory()->create();

    $response = $this->delete(route('courses.destroy', $course));

    $response->assertRedirect(route('courses.index'));
    $response->assertSessionHas('success');
    $this->assertSoftDeleted($course);
});

it('restores a soft deleted course', function () {
    $course = Course::factory()->create();
    $course->delete();

    $response = $this->post(route('courses.restore', $course->id));

    $response->assertRedirect(route('courses.show', $course));
    $this->assertNotSoftDeleted($course);
});

it('permanently deletes a course', function () {
    $course = Course::factory()->create();

    $response = $this->delete(route('courses.forceDestroy', $course->id));

    $response->assertRedirect(route('courses.index'));
    expect(Course::withTrashed()->count())->toBe(0);
});
