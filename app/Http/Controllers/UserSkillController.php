<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserSkillController extends Controller
{
    /** @var list<string> */
    public const PROFICIENCIES = ['beginner', 'intermediate', 'expert'];

    /**
     * Overview of the skills the user teaches and wants to learn.
     */
    public function index(Request $request): View
    {
        $user = $request->user()->load([
            'offeredSkills' => fn ($query) => $query->orderBy('name'),
            'wantedSkills' => fn ($query) => $query->orderBy('name'),
        ]);

        return view('profile.skills.index', [
            'offeredSkills' => $user->offeredSkills,
            'wantedSkills' => $user->wantedSkills,
        ]);
    }

    public function create(): View
    {
        return view('profile.skills.create', [
            'categories' => $this->categories(),
        ]);
    }

    /**
     * Bulk editor: tick the skills you offer / want and set your level.
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->load(['offeredSkills', 'wantedSkills']);

        return view('profile.skills.edit', [
            'allSkills' => Skill::orderBy('category')->orderBy('name')->get(),
            'userOfferedIds' => $user->offeredSkills->pluck('id')->all(),
            'userWantedIds' => $user->wantedSkills->pluck('id')->all(),
            'userProficiency' => $user->offeredSkills->pluck('pivot.proficiency', 'id')->all(),
            'categories' => $this->categories(),
        ]);
    }

    public function show(Request $request, Skill $skill): View
    {
        $userId = $request->user()->id;

        return view('profile.skills.show', [
            'skill' => $skill,
            'offerers' => $skill->offeredBy()->where('users.id', '!=', $userId)->orderBy('name')->get(),
            'seekers' => $skill->wantedBy()->where('users.id', '!=', $userId)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'offered_skills' => ['array'],
            'offered_skills.*' => ['integer', 'exists:skills,id'],
            'wanted_skills' => ['array'],
            'wanted_skills.*' => ['integer', 'exists:skills,id'],
            'proficiency' => ['array'],
            'proficiency.*' => ['nullable', Rule::in(self::PROFICIENCIES)],
        ]);

        $offeredData = [];
        foreach ($validated['offered_skills'] ?? [] as $skillId) {
            $offeredData[$skillId] = [
                'proficiency' => $validated['proficiency'][$skillId] ?? null,
            ];
        }

        $user = $request->user();

        // Each relationship is scoped by withPivotValue('type', ...), so a sync only touches its own rows.
        $user->offeredSkills()->sync($offeredData);
        $user->wantedSkills()->sync($validated['wanted_skills'] ?? []);

        return redirect()->route('profile.skills.index')
            ->with('success', 'Your skills have been updated successfully.');
    }

    /**
     * Create a brand-new skill and attach it to the current user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60', 'unique:skills,name'],
            'category' => ['nullable', 'string', 'max:60'],
            'type' => ['required', Rule::in(['offered', 'wanted', 'both'])],
            'proficiency' => ['nullable', Rule::in(self::PROFICIENCIES)],
        ]);

        $name = trim($validated['name']);

        $skill = Skill::create([
            'name' => $name,
            'slug' => Skill::uniqueSlug($name),
            'category' => filled($validated['category'] ?? null) ? trim($validated['category']) : 'General',
        ]);

        $user = $request->user();

        if (in_array($validated['type'], ['offered', 'both'], true)) {
            $user->offeredSkills()->attach($skill->id, [
                'proficiency' => $validated['proficiency'] ?? null,
            ]);
        }

        if (in_array($validated['type'], ['wanted', 'both'], true)) {
            $user->wantedSkills()->attach($skill->id);
        }

        return redirect()->route('profile.skills.index')
            ->with('success', "\"{$skill->name}\" was added to your skills.");
    }

    /**
     * Remove a skill from the user's profile (the skill itself stays in the catalogue).
     */
    public function destroy(Request $request, Skill $skill): RedirectResponse
    {
        $request->validate([
            'type' => ['nullable', Rule::in(['offered', 'wanted'])],
        ]);

        $user = $request->user();
        $type = $request->input('type');

        if ($type !== 'wanted') {
            $user->offeredSkills()->detach($skill->id);
        }

        if ($type !== 'offered') {
            $user->wantedSkills()->detach($skill->id);
        }

        return redirect()->route('profile.skills.index')
            ->with('success', "\"{$skill->name}\" was removed from your profile.");
    }

    /**
     * @return Collection<int, string>
     */
    private function categories(): Collection
    {
        return Skill::whereNotNull('category')->distinct()->orderBy('category')->pluck('category');
    }
}
