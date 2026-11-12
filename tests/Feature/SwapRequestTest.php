<?php

use App\Models\Skill;
use App\Models\SwapRequest;
use App\Models\User;

beforeEach(function () {
    $this->php = Skill::factory()->create(['name' => 'PHP']);
    $this->guitar = Skill::factory()->create(['name' => 'Guitar']);

    // Me: teaches PHP, wants Guitar.
    $this->me = User::factory()->create();
    $this->me->offeredSkills()->attach($this->php);
    $this->me->wantedSkills()->attach($this->guitar);

    // Partner: teaches Guitar, wants PHP.
    $this->partner = User::factory()->create(['name' => 'Gina Guitar']);
    $this->partner->offeredSkills()->attach($this->guitar);
    $this->partner->wantedSkills()->attach($this->php);

    // Someone who teaches Guitar but does not want PHP.
    $this->stranger = User::factory()->create(['name' => 'Sam Stranger']);
    $this->stranger->offeredSkills()->attach($this->guitar);
});

test('the match search finds only mirror-image partners', function () {
    $this->actingAs($this->me)
        ->get(route('matches.index', ['wanted_skill_id' => $this->guitar->id, 'offered_skill_id' => $this->php->id]))
        ->assertOk()
        ->assertSee('Gina Guitar')
        ->assertDontSee('Sam Stranger')
        ->assertSee('Request Skill Swap');
});

test('a user can send a swap request to a match once', function () {
    $payload = [
        'receiver_id' => $this->partner->id,
        'offered_skill_id' => $this->php->id,
        'requested_skill_id' => $this->guitar->id,
        'initial_message' => 'Hi!',
    ];

    $this->actingAs($this->me)->post(route('swap.request.store'), $payload)->assertSessionHasNoErrors();
    $this->post(route('swap.request.store'), $payload)->assertSessionHasNoErrors();

    expect(SwapRequest::count())->toBe(1)
        ->and(SwapRequest::first())
        ->sender_id->toBe($this->me->id)
        ->status->toBe('pending')
        ->initial_message->toBe('Hi!');
});

test('a swap request to a non-match or to yourself is rejected', function () {
    $this->actingAs($this->me);

    $this->post(route('swap.request.store'), [
        'receiver_id' => $this->stranger->id,
        'offered_skill_id' => $this->php->id,
        'requested_skill_id' => $this->guitar->id,
    ])->assertSessionHasErrors('receiver_id');

    $this->post(route('swap.request.store'), [
        'receiver_id' => $this->me->id,
        'offered_skill_id' => $this->php->id,
        'requested_skill_id' => $this->guitar->id,
    ])->assertSessionHasErrors('receiver_id');

    expect(SwapRequest::count())->toBe(0);
});

function makeRequest(User $sender, User $receiver, Skill $offered, Skill $requested): SwapRequest
{
    return SwapRequest::create([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'offered_skill_id' => $offered->id,
        'requested_skill_id' => $requested->id,
    ]);
}

test('only the receiver can accept, and accepted swaps can be completed', function () {
    $swap = makeRequest($this->me, $this->partner, $this->php, $this->guitar);

    $this->actingAs($this->me)
        ->patch(route('swap.request.update', $swap), ['status' => 'accepted'])
        ->assertForbidden();

    $this->actingAs($this->partner)
        ->patch(route('swap.request.update', $swap), ['status' => 'accepted'])
        ->assertRedirect();
    expect($swap->fresh()->status)->toBe('accepted');

    $this->actingAs($this->me)
        ->patch(route('swap.request.update', $swap), ['status' => 'completed'])
        ->assertRedirect();
    expect($swap->fresh()->status)->toBe('completed');
});

test('outsiders cannot view or touch a swap request', function () {
    $swap = makeRequest($this->me, $this->partner, $this->php, $this->guitar);

    $this->actingAs($this->stranger);
    $this->get(route('swap.request.show', $swap))->assertForbidden();
    $this->patch(route('swap.request.update', $swap), ['status' => 'declined'])->assertForbidden();
});

test('opening a swap request leads to the chat with the other person', function () {
    $swap = makeRequest($this->me, $this->partner, $this->php, $this->guitar);

    $this->actingAs($this->partner)
        ->get(route('swap.request.show', $swap))
        ->assertRedirect(route('messages.show', $this->me));

    $this->get(route('messages.show', $this->me))
        ->assertOk()
        ->assertSee('Accept')
        ->assertSee('Decline');

    $this->get(route('swap.request.index'))->assertOk()->assertSee($this->me->name);
});

test('a request note is posted into the private chat', function () {
    $this->actingAs($this->me)->post(route('swap.request.store'), [
        'receiver_id' => $this->partner->id,
        'offered_skill_id' => $this->php->id,
        'requested_skill_id' => $this->guitar->id,
        'initial_message' => 'Hi Gina, guitar for PHP?',
    ]);

    $this->actingAs($this->partner)
        ->get(route('messages.index'))
        ->assertOk()
        ->assertSee('Hi Gina, guitar for PHP?');
});

test('with no filters everyone who has skills is listed, best match first', function () {
    User::factory()->create(['name' => 'Nora Noskills']);

    $response = $this->actingAs($this->me)->get(route('matches.index'));

    $response->assertOk()
        ->assertSeeInOrder(['Gina Guitar', 'Sam Stranger'])
        ->assertSee('Perfect match for you')
        ->assertDontSee('Nora Noskills');

    // The perfect match gets a pre-filled request: I learn Guitar, I teach PHP.
    expect($response->viewData('suggestions')[$this->partner->id])
        ->learn->toBe($this->guitar->id)
        ->teach->toBe($this->php->id)
        ->and($response->viewData('suggestions')[$this->stranger->id]['teach'])->toBeNull();
});

test('people can be filtered by a single skill or by name', function () {
    $this->actingAs($this->me);

    $this->get(route('matches.index', ['wanted_skill_id' => $this->guitar->id]))
        ->assertSee('Gina Guitar')
        ->assertSee('Sam Stranger');

    $this->get(route('matches.index', ['offered_skill_id' => $this->php->id]))
        ->assertSee('Gina Guitar')
        ->assertDontSee('Sam Stranger');

    $this->get(route('matches.index', ['q' => 'sam']))
        ->assertSee('Sam Stranger')
        ->assertDontSee('Gina Guitar');
});
