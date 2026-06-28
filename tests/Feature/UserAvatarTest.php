<?php

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

/**
 * Attach a Rick and Morty avatar to the given user, mirroring the seeder.
 */
function giveAvatar(User $user): User
{
    $user->addMedia(database_path('rickandmorty/avatars/103.jpeg'))
        ->preservingOriginal()
        ->toMediaCollection('avatars');

    return $user->fresh();
}

it('exposes thumb and full avatar urls on a user with an avatar', function () {
    $user = giveAvatar(User::factory()->create());

    expect($user->avatar)->toBeArray()
        ->and($user->avatar['thumb'])->toContain('conversions')
        ->and($user->avatar['thumb'])->toContain('thumb')
        ->and($user->avatar['full'])->toContain('103')
        ->and($user->avatar['full'])->not->toContain('conversions');
});

it('returns a null avatar when the user has no media', function () {
    $user = User::factory()->create();

    expect($user->avatar)->toBeNull();
});

it('includes instructor and student avatars in the course show payload', function () {
    $this->actingAs(userWithRole(UserRole::Admin));

    $instructor = giveAvatar(User::factory()->create());
    $student = giveAvatar(User::factory()->create());

    $course = Course::factory()->create();
    $course->instructors()->attach($instructor, ['is_instructor' => true]);
    $course->students()->attach($student, ['is_instructor' => false]);

    $this->get(route('courses.show', $course))
        ->assertInertia(fn (Assert $page) => $page
            ->where('course.instructors.0.avatar.thumb', $instructor->avatar['thumb'])
            ->where('course.instructors.0.avatar.full', $instructor->avatar['full'])
            ->where('course.students.0.avatar.thumb', $student->avatar['thumb'])
        );
});
