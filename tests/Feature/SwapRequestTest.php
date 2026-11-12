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
    $this->post(route('swap.request.messages.store', $swap), ['body' => 'hi'])->assertForbidden();
});

test('participants can chat and the inbox lists the request', function () {
    $swap = makeRequest($this->me, $this->partner, $this->php, $this->guitar);

    $this->actingAs($this->me)
        ->post(route('swap.request.messages.store', $swap), ['body' => 'When are you free?'])
        ->assertRedirect(route('swap.request.show', $swap));

    $this->actingAs($this->partner)
        ->get(route('swap.request.show', $swap))
        ->assertOk()
        ->assertSee('When are you free?');

    expect($swap->messages()->first()->is_read)->toBeTrue();

    $this->get(route('swap.request.index'))->assertOk()->assertSee($this->me->name);
});
