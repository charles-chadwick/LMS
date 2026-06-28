<?php

use App\Enums\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('returns a successful response for the courses index', function () {
    $this->actingAs(userWithRole(UserRole::Student));

    $response = $this->get(route('courses.index'));

    $response->assertOk();
});

it('redirects guests away from the courses index', function () {
    $this->get(route('courses.index'))->assertRedirect(route('login'));
});
