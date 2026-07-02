<?php

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(userWithRole(UserRole::Admin));
});

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
        'description' => '<p>An introductory course</p>',
    ]);

    $course = Course::firstWhere('code', 'NEW-101');

    expect($course)->not->toBeNull()
        ->and($course->title)->toBe('New Course')
        ->and($course->description)->toBe('<p>An introductory course</p>');
    $response->assertRedirect(route('courses.show', $course));
    $response->assertSessionHas('success');
});

it('creates a course without a description', function () {
    $response = $this->post(route('courses.store'), [
        'status' => CourseStatus::Draft->value,
        'title' => 'No Description Course',
        'code' => 'NODESC-101',
    ]);

    $course = Course::firstWhere('code', 'NODESC-101');

    expect($course)->not->toBeNull()
        ->and($course->description)->toBeNull();
    $response->assertRedirect(route('courses.show', $course));
});

it('updates a course description', function () {
    $course = Course::factory()->create(['description' => null]);

    $this->put(route('courses.update', $course), [
        'status' => CourseStatus::Published->value,
        'title' => $course->title,
        'code' => $course->code,
        'description' => '<p>Updated description</p>',
    ]);

    expect($course->fresh()->description)->toBe('<p>Updated description</p>');
});

it('sanitizes dangerous markup from the course description', function () {
    $this->post(route('courses.store'), [
        'status' => CourseStatus::Draft->value,
        'title' => 'XSS Course',
        'code' => 'XSS-101',
        'description' => '<p>Safe</p><script>alert(1)</script>',
    ]);

    $description = Course::firstWhere('code', 'XSS-101')->description;

    expect($description)->toContain('Safe')
        ->and($description)->not->toContain('<script');
});

it('stores a cover image when creating a course', function () {
    Storage::fake('public');

    $this->post(route('courses.store'), [
        'status' => CourseStatus::Draft->value,
        'title' => 'Course With Cover',
        'code' => 'COVER-101',
        'cover' => UploadedFile::fake()->image('cover.jpg', 800, 450),
    ]);

    $course = Course::firstWhere('code', 'COVER-101');

    expect($course->getFirstMedia('cover'))->not->toBeNull()
        ->and($course->cover)->toBeArray()
        ->and($course->cover['thumb'])->toContain('thumb')
        ->and($course->cover['full'])->not->toContain('conversions');
});

it('replaces the cover image when updating a course', function () {
    Storage::fake('public');

    $course = Course::factory()->withCover()->create();
    $original_media_id = $course->getFirstMedia('cover')->id;

    $this->put(route('courses.update', $course), [
        'status' => $course->status->value,
        'title' => $course->title,
        'code' => $course->code,
        'cover' => UploadedFile::fake()->image('new-cover.jpg', 800, 450),
    ]);

    $course->refresh();

    expect($course->getMedia('cover'))->toHaveCount(1)
        ->and($course->getFirstMedia('cover')->id)->not->toBe($original_media_id);
});

it('removes the cover image when remove_cover is set', function () {
    Storage::fake('public');

    $course = Course::factory()->withCover()->create();

    $this->put(route('courses.update', $course), [
        'status' => $course->status->value,
        'title' => $course->title,
        'code' => $course->code,
        'remove_cover' => true,
    ]);

    expect($course->fresh()->cover)->toBeNull();
});

it('rejects a cover that is not an image', function () {
    Storage::fake('public');

    $response = $this->post(route('courses.store'), [
        'status' => CourseStatus::Draft->value,
        'title' => 'Bad Cover Course',
        'code' => 'BADCOVER-101',
        'cover' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
    ]);

    $response->assertSessionHasErrors('cover');
    expect(Course::firstWhere('code', 'BADCOVER-101'))->toBeNull();
});

it('exposes the cover image url on the course show payload', function () {
    Storage::fake('public');

    $course = Course::factory()->withCover()->create();

    $this->get(route('courses.show', $course))
        ->assertInertia(fn (Assert $page) => $page
            ->where('course.cover.thumb', $course->cover['thumb'])
            ->where('course.cover.full', $course->cover['full'])
        );
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

it('loads only the first page of the student roster with the full count', function () {
    $course = Course::factory()->create();
    $instructor = userWithRole(UserRole::Instructor);
    $course->instructors()->attach($instructor, ['is_instructor' => true]);
    $students = collect(range(1, 30))->map(fn () => userWithRole(UserRole::Student));
    $students->each(fn ($student) => $course->students()->attach($student, ['is_instructor' => false]));

    $this->actingAs($instructor)
        ->get(route('courses.show', $course))
        ->assertInertia(fn (Assert $page) => $page
            ->has('course.students', 25)
            ->where('course.students_count', 30));
});

it('tie-breaks the student roster ordering by id when first names are identical', function () {
    $course = Course::factory()->create();
    $instructor = userWithRole(UserRole::Instructor);
    $course->instructors()->attach($instructor, ['is_instructor' => true]);

    $students = collect(range(1, 30))->map(fn () => User::factory()->create([
        'role' => UserRole::Student,
        'first_name' => 'Zoe',
    ]));
    $students->reverse()->each(fn ($student) => $course->students()->attach($student, ['is_instructor' => false]));

    $expected_ids = $students->pluck('id')->sort()->values()->take(25)->all();

    $this->actingAs($instructor)
        ->get(route('courses.show', $course))
        ->assertInertia(function (Assert $page) use ($expected_ids) {
            $page->has('course.students', 25);

            $returned_ids = collect($page->toArray()['props']['course']['students'])->pluck('id')->all();
            expect($returned_ids)->toBe($expected_ids);
        });
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
        ->status->toBe(CourseStatus::Published);
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
