<?php

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Page;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('redirects guests to login from courses', function () {
    $this->get(route('courses.index'))->assertRedirect(route('login'));
});

it('lets any authenticated user view courses', function () {
    $this->actingAs(userWithRole('Student'));

    $this->get(route('courses.index'))->assertOk();
});

it('forbids students from creating courses', function () {
    $this->actingAs(userWithRole('Student'));

    $this->post(route('courses.store'), [
        'status' => CourseStatus::Draft->value,
        'title' => 'Nope',
        'code' => 'NOPE-1',
    ])->assertForbidden();

    expect(Course::count())->toBe(0);
});

it('lets instructors create courses', function () {
    $this->actingAs(userWithRole('Instructor'));

    $this->post(route('courses.store'), [
        'status' => CourseStatus::Draft->value,
        'title' => 'Mine',
        'code' => 'MINE-1',
    ])->assertRedirect();

    expect(Course::count())->toBe(1);
});

it('lets an instructor update a course they teach', function () {
    $instructor = userWithRole('Instructor');
    $course = Course::factory()->create();
    $course->instructors()->attach($instructor, ['is_instructor' => true]);

    $this->actingAs($instructor)
        ->put(route('courses.update', $course), [
            'status' => CourseStatus::Published->value,
            'title' => 'Updated',
            'code' => $course->code,
        ])
        ->assertRedirect(route('courses.show', $course));

    expect($course->fresh()->title)->toBe('Updated');
});

it('forbids an instructor from updating a course they do not teach', function () {
    $instructor = userWithRole('Instructor');
    $course = Course::factory()->create(['title' => 'Hands Off']);

    $this->actingAs($instructor)
        ->put(route('courses.update', $course), [
            'status' => CourseStatus::Published->value,
            'title' => 'Hijacked',
            'code' => $course->code,
        ])
        ->assertForbidden();

    expect($course->fresh()->title)->toBe('Hands Off');
});

it('lets an admin update any course', function () {
    $course = Course::factory()->create();

    $this->actingAs(userWithRole('Admin'))
        ->put(route('courses.update', $course), [
            'status' => CourseStatus::Published->value,
            'title' => 'Admin Edit',
            'code' => $course->code,
        ])
        ->assertRedirect(route('courses.show', $course));

    expect($course->fresh()->title)->toBe('Admin Edit');
});

it('forbids an instructor from adding a page to a course they do not teach', function () {
    $instructor = userWithRole('Instructor');
    $course = Course::factory()->create();

    $this->actingAs($instructor)
        ->post(route('pages.store'), [
            'course_id' => $course->id,
            'status' => CourseStatus::Draft->value,
            'title' => 'Intruder',
            'content' => '<p>x</p>',
        ])
        ->assertForbidden();

    expect(Page::count())->toBe(0);
});

it('lets an instructor add a page to a course they teach', function () {
    $instructor = userWithRole('Instructor');
    $course = Course::factory()->create();
    $course->instructors()->attach($instructor, ['is_instructor' => true]);

    $this->actingAs($instructor)
        ->post(route('pages.store'), [
            'course_id' => $course->id,
            'status' => CourseStatus::Draft->value,
            'title' => 'Lesson',
            'content' => '<p>ok</p>',
        ])
        ->assertRedirect();

    expect(Page::count())->toBe(1);
});

it('forbids an instructor from deleting a page in another course', function () {
    $instructor = userWithRole('Instructor');
    $page = Page::factory()->create();

    $this->actingAs($instructor)
        ->delete(route('pages.destroy', $page))
        ->assertForbidden();

    $this->assertNotSoftDeleted($page);
});
