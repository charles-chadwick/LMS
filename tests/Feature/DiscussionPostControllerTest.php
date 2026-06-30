<?php

use App\Enums\UserRole;
use App\Events\DiscussionPostCreated;
use App\Models\Course;
use App\Models\Discussion;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

it('lets an enrolled student reply to an open discussion', function () {
    $course = Course::factory()->create();
    $student = enrolledStudent($course);
    $discussion = Discussion::factory()->forCourse($course)->create();

    $this->actingAs($student)->post(route('courses.discussions.posts.store', [$course, $discussion]), [
        'content' => '<p>Here is my answer.</p>',
    ])->assertRedirect(route('courses.discussions.show', [$course, $discussion]));

    $post = $discussion->posts()->latest('id')->first();

    expect($post->created_by_id)->toBe($student->id);
});

it('broadcasts a reply to the course discussions channel', function () {
    Event::fake([DiscussionPostCreated::class]);

    $course = Course::factory()->create();
    $student = enrolledStudent($course);
    $discussion = Discussion::factory()->forCourse($course)->create();

    $this->actingAs($student)->post(route('courses.discussions.posts.store', [$course, $discussion]), [
        'content' => '<p>Broadcast me.</p>',
    ])->assertRedirect();

    Event::assertDispatched(DiscussionPostCreated::class, function (DiscussionPostCreated $event) use ($course, $discussion, $student) {
        $payload = $event->broadcastWith();
        $channels = $event->broadcastOn();

        return $payload['discussion_id'] === $discussion->id
            && $payload['course_id'] === $course->id
            && $payload['author_id'] === $student->id
            && $channels[0] instanceof PrivateChannel
            && $channels[0]->name === "private-courses.{$course->id}.discussions";
    });
});

it('does not broadcast when a reply is rejected', function () {
    Event::fake([DiscussionPostCreated::class]);

    $course = Course::factory()->create();
    $student = enrolledStudent($course);
    $discussion = Discussion::factory()->forCourse($course)->closed()->create();

    $this->actingAs($student)->post(route('courses.discussions.posts.store', [$course, $discussion]), [
        'content' => '<p>Should not send.</p>',
    ])->assertForbidden();

    Event::assertNotDispatched(DiscussionPostCreated::class);
});

it('forbids replying to a closed discussion', function () {
    $course = Course::factory()->create();
    $student = enrolledStudent($course);
    $discussion = Discussion::factory()->forCourse($course)->closed()->create();

    $this->actingAs($student)->post(route('courses.discussions.posts.store', [$course, $discussion]), [
        'content' => '<p>Too late?</p>',
    ])->assertForbidden();
});

it('forbids a non-participant from replying', function () {
    $course = Course::factory()->create();
    $discussion = Discussion::factory()->forCourse($course)->create();

    $this->actingAs(userWithRole(UserRole::Student))
        ->post(route('courses.discussions.posts.store', [$course, $discussion]), [
            'content' => '<p>Sneaking in.</p>',
        ])->assertForbidden();
});

it('lets only managers reply to an announcement', function () {
    [$course, $instructor] = courseWithManager();
    $student = enrolledStudent($course);
    $discussion = Discussion::factory()->forCourse($course)->announcement()->create();

    $this->actingAs($student)->post(route('courses.discussions.posts.store', [$course, $discussion]), [
        'content' => '<p>Can I reply?</p>',
    ])->assertForbidden();

    $this->actingAs($instructor)->post(route('courses.discussions.posts.store', [$course, $discussion]), [
        'content' => '<p>Manager reply.</p>',
    ])->assertRedirect();
});

it('lets the author edit their own post', function () {
    $course = Course::factory()->create();
    $student = enrolledStudent($course);
    $discussion = Discussion::factory()->forCourse($course)->create();
    $post = $discussion->posts()->create(['content' => '<p>original</p>', 'created_by_id' => $student->id]);

    $this->actingAs($student)->put(route('courses.discussions.posts.update', [$course, $discussion, $post]), [
        'content' => '<p>edited</p>',
    ])->assertRedirect();

    expect($post->fresh()->content)->toContain('edited');
});

it('forbids a student from editing another student\'s post', function () {
    $course = Course::factory()->create();
    $author = enrolledStudent($course);
    $other = enrolledStudent($course);
    $discussion = Discussion::factory()->forCourse($course)->create();
    $post = $discussion->posts()->create(['content' => '<p>original</p>', 'created_by_id' => $author->id]);

    $this->actingAs($other)->put(route('courses.discussions.posts.update', [$course, $discussion, $post]), [
        'content' => '<p>hijack</p>',
    ])->assertForbidden();
});

it('lets a manager delete any post', function () {
    [$course, $instructor] = courseWithManager();
    $author = enrolledStudent($course);
    $discussion = Discussion::factory()->forCourse($course)->create();
    $post = $discussion->posts()->create(['content' => '<p>spam</p>', 'created_by_id' => $author->id]);

    $this->actingAs($instructor)->delete(route('courses.discussions.posts.destroy', [$course, $discussion, $post]))
        ->assertRedirect();

    expect($post->fresh()->trashed())->toBeTrue();
});
