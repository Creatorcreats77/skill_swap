<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

test('anyone can message anyone, and both sides share one conversation', function () {
    $alice = User::factory()->create(['name' => 'Alice']);
    $bob = User::factory()->create(['name' => 'Bob']);

    $this->actingAs($alice)
        ->post(route('messages.store', $bob), ['body' => 'Hi Bob!'])
        ->assertRedirect(route('messages.show', $bob).'#latest');

    $this->actingAs($bob)
        ->post(route('messages.store', $alice), ['body' => 'Hey Alice'])
        ->assertRedirect();

    expect(Conversation::count())->toBe(1)
        ->and(Message::count())->toBe(2);

    $this->actingAs($alice)
        ->get(route('messages.show', $bob))
        ->assertOk()
        ->assertSeeInOrder(['Hi Bob!', 'Hey Alice']);
});

test('the inbox lists conversations with unread counts, and opening one marks it read', function () {
    $me = User::factory()->create();
    $friend = User::factory()->create(['name' => 'Friendly Fran']);

    Conversation::between($friend, $me)->send($friend, 'Are you free tomorrow?');
    Conversation::between($friend, $me)->send($friend, 'For a guitar lesson?');

    expect(Conversation::unreadCountFor($me))->toBe(2);

    $this->actingAs($me)
        ->get(route('messages.index'))
        ->assertOk()
        ->assertSee('Friendly Fran')
        ->assertSee('For a guitar lesson?');

    $this->get(route('messages.show', $friend))->assertOk();

    expect(Conversation::unreadCountFor($me))->toBe(0)
        ->and(Conversation::unreadCountFor($friend))->toBe(0);
});

test('an empty chat page can be opened without creating a conversation', function () {
    $me = User::factory()->create();
    $other = User::factory()->create(['name' => 'Quiet Quinn']);

    $this->actingAs($me)
        ->get(route('messages.show', $other))
        ->assertOk()
        ->assertSee('Start a conversation with Quiet Quinn');

    expect(Conversation::count())->toBe(0);
});

test('people cannot read other people\'s conversations or message themselves', function () {
    [$a, $b, $snoop] = User::factory()->count(3)->create();
    Conversation::between($a, $b)->send($a, 'top secret plan');

    $this->actingAs($snoop)
        ->get(route('messages.show', $a))
        ->assertOk()
        ->assertDontSee('top secret plan');

    $this->get(route('messages.index'))->assertDontSee('top secret plan');

    $this->post(route('messages.store', $snoop), ['body' => 'me myself'])->assertStatus(422);
    $this->get(route('messages.show', $snoop))->assertRedirect(route('messages.index'));
});

test('empty messages are rejected', function () {
    [$a, $b] = User::factory()->count(2)->create();

    $this->actingAs($a)
        ->post(route('messages.store', $b), ['body' => '   '])
        ->assertSessionHasErrors('body');

    expect(Message::count())->toBe(0);
});
