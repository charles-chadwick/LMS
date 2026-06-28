<?php

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Page;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(userWithRole('Admin'));
});

it('shows the create form with the list of courses', function () {
    Course::factory()->count(2)->create();

    $response = $this->get(route('pages.create'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Pages/Form')
        ->has('courses', 2)
        ->has('status_options')
    );
});

it('creates a page and appends it after existing pages', function () {
    $course = Course::factory()->create();
    Page::factory()->create(['course_id' => $course->id, 'order' => 1]);
    Page::factory()->create(['course_id' => $course->id, 'order' => 2]);

    $response = $this->post(route('pages.store'), [
        'course_id' => $course->id,
        'status' => CourseStatus::Draft->value,
        'title' => 'Introduction',
        'content' => '<p>Welcome</p>',
    ]);

    $page = Page::firstWhere('title', 'Introduction');

    expect($page)->not->toBeNull()
        ->and($page->course_id)->toBe($course->id)
        ->and($page->order)->toBe(3)
        ->and($page->content)->toBe('<p>Welcome</p>');
    $response->assertRedirect(route('pages.show', $page));
    $response->assertSessionHas('success');
});

it('creates the first page with order one', function () {
    $course = Course::factory()->create();

    $this->post(route('pages.store'), [
        'course_id' => $course->id,
        'status' => CourseStatus::Draft->value,
        'title' => 'First',
        'content' => '<p>First</p>',
    ]);

    expect(Page::firstWhere('title', 'First')->order)->toBe(1);
});

it('sanitizes dangerous markup from page content on create', function () {
    $course = Course::factory()->create();

    $this->post(route('pages.store'), [
        'course_id' => $course->id,
        'status' => CourseStatus::Draft->value,
        'title' => 'XSS',
        'content' => '<p>Safe</p><script>alert(1)</script><img src=x onerror="alert(2)">',
    ]);

    $content = Page::firstWhere('title', 'XSS')->content;

    expect($content)->toContain('Safe')
        ->and($content)->not->toContain('<script')
        ->and($content)->not->toContain('onerror');
});

it('sanitizes dangerous markup from page content on update', function () {
    $page = Page::factory()->create();

    $this->put(route('pages.update', $page), [
        'course_id' => $page->course_id,
        'status' => CourseStatus::Draft->value,
        'title' => $page->title,
        'content' => '<p>Clean</p><script>alert(1)</script>',
    ]);

    expect($page->fresh()->content)->toContain('Clean')
        ->and($page->fresh()->content)->not->toContain('<script');
});

it('validates required fields when creating a page', function () {
    $response = $this->post(route('pages.store'), []);

    $response->assertSessionHasErrors(['course_id', 'status', 'title', 'content']);
    expect(Page::count())->toBe(0);
});

it('rejects a non-existent course when creating a page', function () {
    $response = $this->post(route('pages.store'), [
        'course_id' => 999,
        'status' => CourseStatus::Draft->value,
        'title' => 'Orphan',
        'content' => '<p>Orphan</p>',
    ]);

    $response->assertSessionHasErrors('course_id');
    expect(Page::count())->toBe(0);
});

it('displays a page', function () {
    $page = Page::factory()->create(['title' => 'Lesson One']);

    $response = $this->get(route('pages.show', $page));

    $response->assertOk();
    $response->assertInertia(fn (Assert $assert) => $assert
        ->component('Pages/Show')
        ->where('page.id', $page->id)
        ->where('page.title', 'Lesson One')
        ->has('page.course')
    );
});

it('shows the edit form with the page and courses', function () {
    $page = Page::factory()->create();

    $response = $this->get(route('pages.edit', $page));

    $response->assertOk();
    $response->assertInertia(fn (Assert $assert) => $assert
        ->component('Pages/Form')
        ->where('page.id', $page->id)
        ->has('courses')
        ->has('status_options')
    );
});

it('updates a page', function () {
    $page = Page::factory()->create(['title' => 'Old Title']);

    $response = $this->put(route('pages.update', $page), [
        'course_id' => $page->course_id,
        'status' => CourseStatus::Published->value,
        'title' => 'New Title',
        'content' => '<p>Updated</p>',
    ]);

    $response->assertRedirect(route('pages.show', $page));
    expect($page->fresh())
        ->title->toBe('New Title')
        ->status->toBe(CourseStatus::Published->value)
        ->content->toBe('<p>Updated</p>');
});

it('validates required fields when updating a page', function () {
    $page = Page::factory()->create(['title' => 'Keep Me']);

    $response = $this->put(route('pages.update', $page), [
        'course_id' => '',
        'status' => '',
        'title' => '',
        'content' => '',
    ]);

    $response->assertSessionHasErrors(['course_id', 'status', 'title', 'content']);
    expect($page->fresh()->title)->toBe('Keep Me');
});

it('soft deletes a page', function () {
    $page = Page::factory()->create();

    $response = $this->delete(route('pages.destroy', $page));

    $response->assertRedirect(route('courses.show', $page->course_id));
    $response->assertSessionHas('success');
    $this->assertSoftDeleted($page);
});

it('reorders pages within a course', function () {
    $course = Course::factory()->create();
    $first = Page::factory()->create(['course_id' => $course->id, 'order' => 1]);
    $second = Page::factory()->create(['course_id' => $course->id, 'order' => 2]);
    $third = Page::factory()->create(['course_id' => $course->id, 'order' => 3]);

    $response = $this->put(route('pages.reorder', $course), [
        'pages' => [$third->id, $first->id, $second->id],
    ]);

    $response->assertRedirect(route('courses.show', $course));
    expect($third->fresh()->order)->toBe(1)
        ->and($first->fresh()->order)->toBe(2)
        ->and($second->fresh()->order)->toBe(3);
});

it('rejects reordering with a page from another course', function () {
    $course = Course::factory()->create();
    $own = Page::factory()->create(['course_id' => $course->id, 'order' => 1]);
    $foreign = Page::factory()->create(['order' => 1]);

    $response = $this->put(route('pages.reorder', $course), [
        'pages' => [$own->id, $foreign->id],
    ]);

    $response->assertSessionHasErrors('pages.1');
    expect($own->fresh()->order)->toBe(1);
});
