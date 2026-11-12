<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MatchController extends Controller
{
    /**
     * A match is any other user who teaches what you want to learn and wants to learn what you teach.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'wanted_skill_id' => ['nullable', 'integer', 'exists:skills,id'],
            'offered_skill_id' => ['nullable', 'integer', 'exists:skills,id'],
        ]);

        $user = $request->user();
        $wantedSkillId = isset($validated['wanted_skill_id']) ? (int) $validated['wanted_skill_id'] : null;
        $offeredSkillId = isset($validated['offered_skill_id']) ? (int) $validated['offered_skill_id'] : null;

        $matches = collect();
        $pendingReceiverIds = [];

        if ($wantedSkillId && $offeredSkillId) {
            $matches = User::query()
                ->whereKeyNot($user->id)
                ->matching($wantedSkillId, $offeredSkillId)
                ->with(['offeredSkills', 'wantedSkills'])
                ->orderBy('name')
                ->get();

            $pendingReceiverIds = $user->sentSwapRequests()
                ->where('status', 'pending')
                ->where('offered_skill_id', $offeredSkillId)
                ->where('requested_skill_id', $wantedSkillId)
                ->pluck('receiver_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return view('matches.index', [
            'matches' => $matches,
            'allSkills' => Skill::orderBy('name')->get(),
            'wantedSkillId' => $wantedSkillId,
            'offeredSkillId' => $offeredSkillId,
            'pendingReceiverIds' => $pendingReceiverIds,
            'hasSkills' => $user->skills()->exists(),
        ]);
    }
}
