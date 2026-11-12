<?php

use App\Models\User;

test('guests are sent to the login page', function () {
    $this->get('/')->assertRedirect(route('matches.index'));
    $this->get(route('matches.index'))->assertRedirect(route('login'));
});

test('signed in users can open every main page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    foreach (['matches.index', 'profile.skills.index', 'profile.skills.edit', 'profile.skills.create', 'swap.request.index'] as $route) {
        $this->get(route($route))->assertOk();
    }
});
