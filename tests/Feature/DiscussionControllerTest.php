<?php

use App\Enums\DiscussionStatus;
use App\Enums\DiscussionType;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Discussion;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('lets an enrolled student view the course discussions index', function () {
    $course = Course::factory()->create();
    $student = enrolledStudent($course);

    $this->actingAs($student)
        ->get(route('courses.discussions.index', $course))
        ->assertOk();
});

it('forbids a non-participant from viewing the course discussions index', function () {
    $course = Course::factory()->create();

    $this->actingAs(userWithRole(UserRole::Student))
        ->get(route('courses.discussions.index', $course))
        ->assertForbidden();
});

it('lets an enrolled student start a general discussion with an opening post', function () {
    $course = Course::factory()->create();
    $student = enrolledStudent($course);

    $response = $this->actingAs($student)->post(route('courses.discussions.store', $course), [
        'type' => DiscussionType::General->value,
        'title' => 'How do I submit the assignment?',
        'body' => '<p>I cannot find the upload button.</p>',
    ]);

    $discussion = $course->discussions()->first();

    expect($discussion)->not->toBeNull()
        ->and($discussion->type)->toBe(DiscussionType::General)
        ->and($discussion->created_by_id)->toBe($student->id)
        ->and($discussion->posts()->count())->toBe(1);

    $response->assertRedirect(route('courses.discussions.show', [$course, $discussion]));
});

it('forbids a student from starting an announcement', function () {
    $course = Course::factory()->create();
    $student = enrolledStudent($course);

    $this->actingAs($student)->post(route('courses.discussions.store', $course), [
        'type' => DiscussionType::Announcement->value,
        'title' => 'Important',
        'body' => '<p>Listen up.</p>',
    ])->assertForbidden();
});

it('lets an assigned instructor start an announcement', function () {
    [$course, $instructor] = courseWithManager();

    $this->actingAs($instructor)->post(route('courses.discussions.store', $course), [
        'type' => DiscussionType::Announcement->value,
        'title' => 'Welcome',
        'body' => '<p>Welcome to the course.</p>',
    ])->assertRedirect();

    expect($course->discussions()->first()->type)->toBe(DiscussionType::Announcement);
});

it('forbids a non-participant from starting a discussion', function () {
    $course = Course::factory()->create();

    $this->actingAs(userWithRole(UserRole::Student))->post(route('courses.discussions.store', $course), [
        'type' => DiscussionType::General->value,
        'title' => 'Hello',
        'body' => '<p>Hello.</p>',
    ])->assertForbidden();
});

it('lets the author update their own discussion', function () {
    $course = Course::factory()->create();
    $student = enrolledStudent($course);
    $discussion = Discussion::factory()->forCourse($course)->create(['created_by_id' => $student->id]);

    $this->actingAs($student)->put(route('courses.discussions.update', [$course, $discussion]), [
        'type' => DiscussionType::General->value,
        'title' => 'Updated title',
    ])->assertRedirect(route('courses.discussions.show', [$course, $discussion]));

    expect($discussion->fresh()->title)->toBe('Updated title');
});

it('forbids a student from updating another student\'s discussion', function () {
    $course = Course::factory()->create();
    $author = enrolledStudent($course);
    $other = enrolledStudent($course);
    $discussion = Discussion::factory()->forCourse($course)->create(['created_by_id' => $author->id]);

    $this->actingAs($other)->put(route('courses.discussions.update', [$course, $discussion]), [
        'type' => DiscussionType::General->value,
        'title' => 'Hijacked',
    ])->assertForbidden();
});

it('lets a manager update any discussion', function () {
    [$course, $instructor] = courseWithManager();
    $author = enrolledStudent($course);
    $discussion = Discussion::factory()->forCourse($course)->create(['created_by_id' => $author->id]);

    $this->actingAs($instructor)->put(route('courses.discussions.update', [$course, $discussion]), [
        'type' => DiscussionType::General->value,
        'title' => 'Moderated title',
    ])->assertRedirect();

    expect($discussion->fresh()->title)->toBe('Moderated title');
});

it('forbids a student from promoting their discussion to an announcement', function () {
    $course = Course::factory()->create();
    $student = enrolledStudent($course);
    $discussion = Discussion::factory()->forCourse($course)->create(['created_by_id' => $student->id]);

    $this->actingAs($student)->put(route('courses.discussions.update', [$course, $discussion]), [
        'type' => DiscussionType::Announcement->value,
        'title' => $discussion->title,
    ])->assertSessionHasErrors('type');
});

it('lets a manager close a discussion', function () {
    [$course, $instructor] = courseWithManager();
    $discussion = Discussion::factory()->forCourse($course)->create();

    $this->actingAs($instructor)->patch(route('courses.discussions.setStatus', [$course, $discussion]), [
        'status' => DiscussionStatus::Closed->value,
    ])->assertRedirect();

    expect($discussion->fresh()->status)->toBe(DiscussionStatus::Closed);
});

it('forbids a student from closing a discussion', function () {
    $course = Course::factory()->create();
    $student = enrolledStudent($course);
    $discussion = Discussion::factory()->forCourse($course)->create(['created_by_id' => $student->id]);

    $this->actingAs($student)->patch(route('courses.discussions.setStatus', [$course, $discussion]), [
        'status' => DiscussionStatus::Closed->value,
    ])->assertForbidden();
});

it('lets a manager delete a discussion and its posts', function () {
    [$course, $instructor] = courseWithManager();
    $discussion = Discussion::factory()->forCourse($course)->create();
    $discussion->posts()->create(['content' => '<p>hi</p>']);

    $this->actingAs($instructor)->delete(route('courses.discussions.destroy', [$course, $discussion]))
        ->assertRedirect(route('courses.discussions.index', $course));

    expect($discussion->fresh()->trashed())->toBeTrue()
        ->and($discussion->posts()->count())->toBe(0);
});

it('scopes discussion route binding to the parent course', function () {
    $course = Course::factory()->create();
    $otherCourse = Course::factory()->create();
    $student = enrolledStudent($course);
    $discussion = Discussion::factory()->forCourse($otherCourse)->create();

    $this->actingAs($student)
        ->get(route('courses.discussions.show', [$course, $discussion]))
        ->assertNotFound();
});
