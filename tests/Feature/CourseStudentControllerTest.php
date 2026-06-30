<?php

use App\Actions\Courses\AssignStudent;
use App\Actions\Courses\RemoveStudent;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Group;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('authorizes admins and assigned instructors to manage students', function () {
    $course = Course::factory()->create();
    $instructor = userWithRole(UserRole::Instructor);
    $course->instructors()->attach($instructor, ['is_instructor' => true]);

    $admin = userWithRole(UserRole::Admin);
    $other_instructor = userWithRole(UserRole::Instructor);
    $student = userWithRole(UserRole::Student);

    expect($admin->can('manageStudents', $course))->toBeTrue()
        ->and($instructor->can('manageStudents', $course))->toBeTrue()
        ->and($other_instructor->can('manageStudents', $course))->toBeFalse()
        ->and($student->can('manageStudents', $course))->toBeFalse();
});

it('attaches a user as a student via the AssignStudent action', function () {
    $course = Course::factory()->create();
    $user = userWithRole(UserRole::Student);

    app(AssignStudent::class)->execute($course, $user);

    expect($course->students()->whereKey($user->id)->exists())->toBeTrue();
});

it('detaches a student via the RemoveStudent action', function () {
    $course = Course::factory()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    app(RemoveStudent::class)->execute($course, $student);

    expect($course->students()->whereKey($student->id)->exists())->toBeFalse();
});

it('removes the last student without error', function () {
    $course = Course::factory()->create();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    app(RemoveStudent::class)->execute($course, $student);

    expect($course->students()->count())->toBe(0);
});

it('lets an admin enroll a student', function () {
    [$course] = courseWithManager();
    $student = userWithRole(UserRole::Student);

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->post(route('courses.students.store', $course), ['user_id' => $student->id]);

    $response->assertRedirect(route('courses.show', $course));
    $response->assertSessionHas('success');
    expect($course->students()->whereKey($student->id)->exists())->toBeTrue();
});

it('lets an assigned instructor enroll a student', function () {
    [$course, $instructor] = courseWithManager();
    $student = userWithRole(UserRole::Student);

    $response = $this->actingAs($instructor)
        ->post(route('courses.students.store', $course), ['user_id' => $student->id]);

    $response->assertRedirect(route('courses.show', $course));
    expect($course->students()->count())->toBe(1);
});

it('forbids a non-manager from enrolling a student', function () {
    [$course] = courseWithManager();
    $student = userWithRole(UserRole::Student);

    $response = $this->actingAs(userWithRole(UserRole::Instructor))
        ->post(route('courses.students.store', $course), ['user_id' => $student->id]);

    $response->assertForbidden();
    expect($course->students()->whereKey($student->id)->exists())->toBeFalse();
});

it('rejects enrolling a user without the Student role', function () {
    [$course] = courseWithManager();
    $non_student = userWithRole(UserRole::Instructor);

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->post(route('courses.students.store', $course), ['user_id' => $non_student->id]);

    $response->assertSessionHasErrors('user_id');
    expect($course->students()->whereKey($non_student->id)->exists())->toBeFalse();
});

it('rejects enrolling an already-enrolled student', function () {
    [$course] = courseWithManager();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->post(route('courses.students.store', $course), ['user_id' => $student->id]);

    $response->assertSessionHasErrors('user_id');
    expect($course->students()->count())->toBe(1);
});

it('lets an admin remove a student', function () {
    [$course] = courseWithManager();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->delete(route('courses.students.destroy', ['course' => $course, 'user' => $student]));

    $response->assertRedirect(route('courses.show', $course));
    expect($course->students()->whereKey($student->id)->exists())->toBeFalse();
});

it('forbids a non-manager from removing a student', function () {
    [$course] = courseWithManager();
    $student = userWithRole(UserRole::Student);
    $course->students()->attach($student, ['is_instructor' => false]);

    $response = $this->actingAs(userWithRole(UserRole::Instructor))
        ->delete(route('courses.students.destroy', ['course' => $course, 'user' => $student]));

    $response->assertForbidden();
    expect($course->students()->whereKey($student->id)->exists())->toBeTrue();
});

it('searches assignable students for a manager, excluding the enrolled', function () {
    [$course] = courseWithManager();
    $candidate = userWithRole(UserRole::Student);
    $enrolled = userWithRole(UserRole::Student);
    $course->students()->attach($enrolled, ['is_instructor' => false]);

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->getJson(route('courses.students.assignable', $course));

    $response->assertOk();
    $ids = collect($response->json())->pluck('id');
    expect($ids)->toContain($candidate->id)->not->toContain($enrolled->id);
});

it('filters assignable students by the search term', function () {
    [$course] = courseWithManager();
    $match = userWithRole(UserRole::Student);
    $match->update(['first_name' => 'Searchable', 'last_name' => 'Learner']);
    userWithRole(UserRole::Student); // noise, should not match

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->getJson(route('courses.students.assignable', ['course' => $course, 'search' => 'Searchable']));

    $response->assertOk();
    $ids = collect($response->json())->pluck('id');
    expect($ids)->toContain($match->id)->toHaveCount(1);
});

it('forbids a non-manager from searching assignable students', function () {
    [$course] = courseWithManager();

    $this->actingAs(userWithRole(UserRole::Student))
        ->getJson(route('courses.students.assignable', $course))
        ->assertForbidden();
});

it('rejects enrolling a user who instructs the course', function () {
    [$course] = courseWithManager();
    $dual_role = userWithRole(UserRole::Student);
    $dual_role->assignRole(UserRole::Instructor);
    $course->instructors()->attach($dual_role, ['is_instructor' => true]);

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->post(route('courses.students.store', $course), ['user_id' => $dual_role->id]);

    $response->assertSessionHasErrors('user_id');
    expect($course->students()->whereKey($dual_role->id)->exists())->toBeFalse();
});

it('lets a manager bulk-enroll a group as students', function () {
    [$course] = courseWithManager();
    $group = Group::factory()->create();
    $first_member = userWithRole(UserRole::Student);
    $second_member = userWithRole(UserRole::Student);
    $group->users()->attach([$first_member->id, $second_member->id], ['is_leader' => false]);

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->post(route('courses.students.storeGroup', $course), ['group_id' => $group->id]);

    $response->assertRedirect(route('courses.show', $course));
    $response->assertSessionHas('success');
    expect($course->students()->count())->toBe(2);
});

it('forbids a non-manager from bulk-enrolling a group', function () {
    [$course] = courseWithManager();
    $group = Group::factory()->create();
    $member = userWithRole(UserRole::Student);
    $group->users()->attach($member, ['is_leader' => false]);

    $response = $this->actingAs(userWithRole(UserRole::Instructor))
        ->post(route('courses.students.storeGroup', $course), ['group_id' => $group->id]);

    $response->assertForbidden();
    expect($course->students()->count())->toBe(0);
});

it('rejects bulk-enrolling a non-existent group', function () {
    [$course] = courseWithManager();

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->post(route('courses.students.storeGroup', $course), ['group_id' => 99999]);

    $response->assertSessionHasErrors('group_id');
});

it('searches assignable groups for a manager, filtered by the search term', function () {
    [$course] = courseWithManager();
    $match = Group::factory()->create(['name' => 'Searchable Cohort']);
    Group::factory()->create(['name' => 'Unrelated Team']);

    $response = $this->actingAs(userWithRole(UserRole::Admin))
        ->getJson(route('courses.students.assignable-groups', ['course' => $course, 'search' => 'Searchable']));

    $response->assertOk();
    $ids = collect($response->json())->pluck('id');
    expect($ids)->toContain($match->id)->toHaveCount(1);
});

it('forbids a non-manager from searching assignable groups', function () {
    [$course] = courseWithManager();

    $this->actingAs(userWithRole(UserRole::Student))
        ->getJson(route('courses.students.assignable-groups', $course))
        ->assertForbidden();
});
