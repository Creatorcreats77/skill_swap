<?php

use App\Models\Skill;
use App\Models\User;

test('updating skills syncs offered and wanted lists independently', function () {
    $user = User::factory()->create();
    [$php, $guitar, $spanish] = Skill::factory()->count(3)->create();

    $this->actingAs($user)
        ->put(route('profile.skills.update'), [
            'offered_skills' => [$php->id, $guitar->id],
            'proficiency' => [$php->id => 'expert'],
            'wanted_skills' => [$spanish->id, $php->id],
        ])
        ->assertRedirect(route('profile.skills.index'));

    $user->refresh();
    expect($user->offeredSkills->pluck('id')->sort()->values()->all())->toBe([$php->id, $guitar->id])
        ->and($user->wantedSkills->pluck('id')->sort()->values()->all())->toBe([$php->id, $spanish->id])
        ->and($user->offeredSkills->firstWhere('id', $php->id)->pivot->proficiency)->toBe('expert');

    // Removing everything from "offered" must leave "wanted" untouched.
    $this->put(route('profile.skills.update'), ['wanted_skills' => [$spanish->id, $php->id]]);

    $user->refresh();
    expect($user->offeredSkills)->toBeEmpty()
        ->and($user->wantedSkills)->toHaveCount(2);
});

test('an invalid proficiency is rejected', function () {
    $user = User::factory()->create();
    $skill = Skill::factory()->create();

    $this->actingAs($user)
        ->put(route('profile.skills.update'), [
            'offered_skills' => [$skill->id],
            'proficiency' => [$skill->id => 'wizard'],
        ])
        ->assertSessionHasErrors('proficiency.'.$skill->id);
});

test('creating a skill attaches it with the chosen type and level', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('profile.skills.store'), [
            'name' => 'Pottery',
            'category' => 'Crafts',
            'type' => 'both',
            'proficiency' => 'intermediate',
        ])
        ->assertRedirect(route('profile.skills.index'));

    $skill = Skill::where('name', 'Pottery')->firstOrFail();
    expect($skill->slug)->toBe('pottery')
        ->and($user->offeredSkills()->first()->pivot->proficiency)->toBe('intermediate')
        ->and($user->wantedSkills()->pluck('skills.id')->all())->toBe([$skill->id]);
});

test('skills whose names slugify the same get distinct slugs', function () {
    $user = User::factory()->create();
    Skill::factory()->create(['name' => 'C', 'slug' => 'c']);

    $this->actingAs($user)
        ->post(route('profile.skills.store'), ['name' => 'C++', 'type' => 'wanted'])
        ->assertSessionHasNoErrors();

    expect(Skill::where('name', 'C++')->value('slug'))->toBe('c-2');
});

test('removing a skill only detaches the requested type', function () {
    $user = User::factory()->create();
    $skill = Skill::factory()->create();
    $user->offeredSkills()->attach($skill);
    $user->wantedSkills()->attach($skill);

    $this->actingAs($user)
        ->delete(route('profile.skills.destroy', $skill), ['type' => 'offered'])
        ->assertRedirect(route('profile.skills.index'));

    expect($user->offeredSkills()->count())->toBe(0)
        ->and($user->wantedSkills()->count())->toBe(1)
        ->and(Skill::find($skill->id))->not->toBeNull();
});

test('the skill page lists other people who teach and want it', function () {
    $user = User::factory()->create();
    $teacher = User::factory()->create(['name' => 'Tina Teacher']);
    $learner = User::factory()->create(['name' => 'Larry Learner']);
    $skill = Skill::factory()->create();
    $teacher->offeredSkills()->attach($skill, ['proficiency' => 'expert']);
    $learner->wantedSkills()->attach($skill);

    $this->actingAs($user)
        ->get(route('profile.skills.show', $skill))
        ->assertOk()
        ->assertSee('Tina Teacher')
        ->assertSee('Larry Learner');
});
