<?php

use App\Models\Skill;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a user can update their name, bio, contacts and photo', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'name' => 'Aylar',
            'bio' => 'I love languages.',
            'phone' => '+993 61 234567',
            'telegram' => 'https://t.me/aylar_dev',
            'instagram' => '@aylar.art',
            'show_email' => '0',
            'avatar' => UploadedFile::fake()->image('me.jpg', 300, 300),
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->name)->toBe('Aylar')
        ->and($user->bio)->toBe('I love languages.')
        ->and($user->phone)->toBe('+993 61 234567')
        ->and($user->telegram)->toBe('aylar_dev')
        ->and($user->instagram)->toBe('aylar.art')
        ->and($user->show_email)->toBeFalse()
        ->and($user->avatar)->not->toBeNull();

    Storage::disk('public')->assertExists($user->avatar);
});

test('replacing or removing a photo deletes the old file', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->put(route('profile.update'), ['name' => $user->name, 'avatar' => UploadedFile::fake()->image('a.png')]);
    $first = $user->fresh()->avatar;

    $this->put(route('profile.update'), ['name' => $user->name, 'avatar' => UploadedFile::fake()->image('b.png')]);
    $second = $user->fresh()->avatar;
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);

    $this->put(route('profile.update'), ['name' => $user->name, 'remove_avatar' => '1']);
    expect($user->fresh()->avatar)->toBeNull();
    Storage::disk('public')->assertMissing($second);
});

test('non-image uploads and bad contact values are rejected', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'name' => 'X',
            'avatar' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'),
            'phone' => 'call me maybe',
            'telegram' => 'ab',
        ])
        ->assertSessionHasErrors(['avatar', 'phone', 'telegram']);
});

test('contacts are hidden until a swap is accepted', function () {
    $me = User::factory()->create();
    $other = User::factory()->create(['phone' => '+993 65 000000', 'telegram' => 'other_user']);
    [$a, $b] = Skill::factory()->count(2)->create();

    $this->actingAs($me)
        ->get(route('users.show', $other))
        ->assertOk()
        ->assertSee($other->name)
        ->assertDontSee('+993 65 000000')
        ->assertSee('shared once a skill swap');

    $swap = SwapRequest::create([
        'sender_id' => $me->id,
        'receiver_id' => $other->id,
        'offered_skill_id' => $a->id,
        'requested_skill_id' => $b->id,
    ]);

    $this->get(route('messages.show', $other))->assertDontSee('+993 65 000000');

    $swap->update(['status' => 'accepted']);

    $this->get(route('users.show', $other))->assertSee('+993 65 000000')->assertSee('@other_user');
    $this->get(route('messages.show', $other))->assertSee('+993 65 000000');
});

test('the profile edit page renders', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Save profile');
});
