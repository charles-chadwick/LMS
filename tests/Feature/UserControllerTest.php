<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->admin = userWithRole(UserRole::Admin);
    $this->actingAs($this->admin);
});

it('lists users with course counts on the index', function () {
    User::factory()->count(2)->create();

    $response = $this->get(route('users.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Users/Index')
        ->has('users.data')
        ->has('users.data.0.courses_count')
    );
});

it('filters users by search term', function () {
    User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    User::factory()->create(['first_name' => 'Alan', 'last_name' => 'Turing']);

    $response = $this->get(route('users.index', ['search' => 'Lovelace']));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('users.data', 1)
        ->where('users.data.0.last_name', 'Lovelace')
    );
});

it('filters users by role', function () {
    User::factory()->create(['role' => UserRole::Instructor]);
    User::factory()->create(['role' => UserRole::Student]);

    $response = $this->get(route('users.index', ['role' => UserRole::Instructor->value]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('users.data.0.role', UserRole::Instructor->value)
    );
});

it('creates a user and assigns the matching spatie role', function () {
    $response = $this->post(route('users.store'), [
        'role' => UserRole::Instructor->value,
        'first_name' => 'Grace',
        'last_name' => 'Hopper',
        'email' => 'grace@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::firstWhere('email', 'grace@example.com');

    expect($user)->not->toBeNull()
        ->and($user->full_name)->toBe('Grace Hopper')
        ->and($user->role)->toBe(UserRole::Instructor)
        ->and($user->hasRole(UserRole::Instructor))->toBeTrue()
        ->and(Hash::check('password', $user->password))->toBeTrue();
    $response->assertRedirect(route('users.show', $user));
    $response->assertSessionHas('success');
});

it('validates required fields when creating a user', function () {
    $response = $this->post(route('users.store'), []);

    $response->assertSessionHasErrors(['role', 'first_name', 'last_name', 'email', 'password']);
});

it('rejects a duplicate email when creating a user', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->post(route('users.store'), [
        'role' => UserRole::Student->value,
        'first_name' => 'Dup',
        'last_name' => 'Licate',
        'email' => 'taken@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
});

it('displays a user', function () {
    $user = User::factory()->create();

    $response = $this->get(route('users.show', $user));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Users/Show')
        ->where('user.id', $user->id)
    );
});

it('updates a user and syncs their role', function () {
    $user = User::factory()->create(['role' => UserRole::Student, 'first_name' => 'Old']);

    $response = $this->put(route('users.update', $user), [
        'role' => UserRole::Instructor->value,
        'first_name' => 'New',
        'last_name' => $user->last_name,
        'email' => $user->email,
        'password' => '',
        'password_confirmation' => '',
    ]);

    $response->assertRedirect(route('users.show', $user));
    expect($user->fresh())
        ->first_name->toBe('New')
        ->role->toBe(UserRole::Instructor)
        ->and($user->fresh()->hasRole(UserRole::Instructor))->toBeTrue();
});

it('keeps the existing password when none is supplied on update', function () {
    $user = User::factory()->create();
    $original_hash = $user->password;

    $this->put(route('users.update', $user), [
        'role' => $user->role->value,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'password' => '',
        'password_confirmation' => '',
    ]);

    expect($user->fresh()->password)->toBe($original_hash);
});

it('changes the password when one is supplied on update', function () {
    $user = User::factory()->create();

    $this->put(route('users.update', $user), [
        'role' => $user->role->value,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

it('soft deletes a user', function () {
    $user = User::factory()->create();

    $response = $this->delete(route('users.destroy', $user));

    $response->assertRedirect(route('users.index'));
    $response->assertSessionHas('success');
    $this->assertSoftDeleted($user);
});

it('prevents an admin from deleting themselves', function () {
    $response = $this->delete(route('users.destroy', $this->admin));

    $response->assertForbidden();
    $this->assertNotSoftDeleted($this->admin);
});

it('restores a soft deleted user', function () {
    $user = User::factory()->create();
    $user->delete();

    $response = $this->post(route('users.restore', $user->id));

    $response->assertRedirect(route('users.show', $user));
    $this->assertNotSoftDeleted($user);
});

it('permanently deletes a user', function () {
    $user = User::factory()->create();

    $response = $this->delete(route('users.forceDestroy', $user->id));

    $response->assertRedirect(route('users.index'));
    expect(User::withTrashed()->whereKey($user->id)->exists())->toBeFalse();
});

it('forbids non-admins from listing users', function () {
    $this->actingAs(userWithRole(UserRole::Student));

    $this->get(route('users.index'))->assertForbidden();
});

it('forbids non-admins from creating users', function () {
    $this->actingAs(userWithRole(UserRole::Instructor));

    $this->post(route('users.store'), [
        'role' => UserRole::Student->value,
        'first_name' => 'No',
        'last_name' => 'Access',
        'email' => 'noaccess@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertForbidden();
});
