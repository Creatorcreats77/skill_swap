<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MatchController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $wantedSkillId = $request->input('wanted_skill_id');     // What you want to learn
        $offeredSkillId = $request->input('offered_skill_id'); // What you are teaching

        $matches = collect();

        if ($wantedSkillId && $offeredSkillId) {
            $matches = User::where('id', '!=', $user->id)
                ->whereHas('skills', function ($q) use ($wantedSkillId) {
                    $q->where('skills.id', $wantedSkillId)
                        ->where('user_skills.type', 'offered');
                })
                ->whereHas('skills', function ($q) use ($offeredSkillId) {
                    $q->where('skills.id', $offeredSkillId)
                        ->where('user_skills.type', 'wanted');
                })
                ->with(['skills'])
                ->get();
        }

        $allSkills = \App\Models\Skill::all();

        return view('matches.index', compact('matches', 'allSkills', 'wantedSkillId', 'offeredSkillId'));
    }
}
