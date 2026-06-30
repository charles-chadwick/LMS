<?php

use App\Actions\Courses\EnrollGroupMembers;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseUser;
use App\Models\Group;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('enrolls group members with no existing enrollment as students', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->create();
    $first_member = userWithRole(UserRole::Student);
    $second_member = userWithRole(UserRole::Student);
    $group->users()->attach([$first_member->id, $second_member->id], ['is_leader' => false]);

    $enrolled_count = app(EnrollGroupMembers::class)->execute($course, $group);

    expect($enrolled_count)->toBe(2)
        ->and($course->students()->whereKey($first_member->id)->exists())->toBeTrue()
        ->and($course->students()->whereKey($second_member->id)->exists())->toBeTrue();
});

it('skips members with an active student enrollment', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->create();
    $already_enrolled = userWithRole(UserRole::Student);
    $course->students()->attach($already_enrolled, ['is_instructor' => false]);
    $group->users()->attach($already_enrolled, ['is_leader' => false]);

    $enrolled_count = app(EnrollGroupMembers::class)->execute($course, $group);

    expect($enrolled_count)->toBe(0)
        ->and($course->students()->whereKey($already_enrolled->id)->count())->toBe(1);
});

it('skips members who actively instruct the course', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->create();
    $instructor = userWithRole(UserRole::Instructor);
    $course->instructors()->attach($instructor, ['is_instructor' => true]);
    $group->users()->attach($instructor, ['is_leader' => false]);

    $enrolled_count = app(EnrollGroupMembers::class)->execute($course, $group);

    expect($enrolled_count)->toBe(0)
        ->and($course->students()->whereKey($instructor->id)->exists())->toBeFalse();
});

it('re-enrolls a member whose enrollment was previously soft-deleted', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->create();
    $member = userWithRole(UserRole::Student);
    $group->users()->attach($member, ['is_leader' => false]);

    $enrollment = CourseUser::create([
        'course_id' => $course->id,
        'user_id' => $member->id,
        'is_instructor' => false,
    ]);
    $enrollment->delete();

    $enrolled_count = app(EnrollGroupMembers::class)->execute($course, $group);

    expect($enrolled_count)->toBe(1)
        ->and($course->students()->whereKey($member->id)->exists())->toBeTrue()
        ->and(CourseUser::withTrashed()->where('user_id', $member->id)->count())->toBe(1);
});

it('skips members who lack the Student role', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->create();
    $non_student = userWithRole(UserRole::Instructor);
    $group->users()->attach($non_student, ['is_leader' => false]);

    $enrolled_count = app(EnrollGroupMembers::class)->execute($course, $group);

    expect($enrolled_count)->toBe(0)
        ->and($course->students()->whereKey($non_student->id)->exists())->toBeFalse();
});

it('returns the count of only the members actually enrolled', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->create();
    $fresh = userWithRole(UserRole::Student);
    $already_enrolled = userWithRole(UserRole::Student);
    $course->students()->attach($already_enrolled, ['is_instructor' => false]);
    $non_student = userWithRole(UserRole::Instructor);
    $group->users()->attach([$fresh->id, $already_enrolled->id, $non_student->id], ['is_leader' => false]);

    $enrolled_count = app(EnrollGroupMembers::class)->execute($course, $group);

    expect($enrolled_count)->toBe(1)
        ->and($course->students()->count())->toBe(2);
});
