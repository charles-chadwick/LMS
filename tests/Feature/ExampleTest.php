<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('returns a successful response for the courses index', function () {
    $response = $this->get(route('courses.index'));

    $response->assertOk();
});
