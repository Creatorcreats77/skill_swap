<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserSkillController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'offered_skills'   => ['array'],
            'offered_skills.*' => ['integer', 'exists:skills,id'],
            'wanted_skills'    => ['array'],
            'wanted_skills.*'  => ['integer', 'exists:skills,id'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Sync each type separately so the same skill can be both offered and wanted.
        // offeredSkills()/wantedSkills() filter by pivot type, so each sync only touches its own rows.
        $user->offeredSkills()->syncWithPivotValues($validated['offered_skills'] ?? [], ['type' => 'offered']);
        $user->wantedSkills()->syncWithPivotValues($validated['wanted_skills'] ?? [], ['type' => 'wanted']);

        return back()->with('success', 'Skills updated successfully!');
    }

    public function edit()
    {
        $user = auth()->user();
        $allSkills = \App\Models\Skill::all();

        $userOfferedIds = $user->offeredSkills()->pluck('skills.id')->toArray();
        $userWantedIds = $user->wantedSkills()->pluck('skills.id')->toArray();

        return view('profile.skills', compact('allSkills', 'userOfferedIds', 'userWantedIds'));
    }
}
