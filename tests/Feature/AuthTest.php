<?php

use App\Models\User;

test('login and register pages render', function () {
    $this->get(route('login'))->assertOk();
    $this->get(route('register'))->assertOk();
});

test('a visitor can register and is sent to the skills editor', function () {
    $response = $this->post(route('register'), [
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('profile.skills.edit'));
    $this->assertAuthenticated();
});

test('a user can log in and log out', function () {
    $user = User::factory()->create();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('matches.index'));
    $this->assertAuthenticatedAs($user);

    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('wrong credentials are rejected', function () {
    $user = User::factory()->create();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'nope'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});
