<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MatchController extends Controller
{
    /**
     * Directory of everyone who has listed skills, best swap partners first.
     *
     * Optional filters: a skill you want to learn (they teach it), a skill you can teach
     * (they want it) and a name search.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'wanted_skill_id' => ['nullable', 'integer', 'exists:skills,id'],
            'offered_skill_id' => ['nullable', 'integer', 'exists:skills,id'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user()->load(['offeredSkills', 'wantedSkills']);
        $wantedSkillId = isset($validated['wanted_skill_id']) ? (int) $validated['wanted_skill_id'] : null;
        $offeredSkillId = isset($validated['offered_skill_id']) ? (int) $validated['offered_skill_id'] : null;
        $search = trim($validated['q'] ?? '');

        $myOfferedIds = $user->offeredSkills->pluck('id')->map(fn ($id) => (int) $id)->all();
        $myWantedIds = $user->wantedSkills->pluck('id')->map(fn ($id) => (int) $id)->all();

        $query = User::query()
            ->whereKeyNot($user->id)
            ->whereHas('skills')
            ->when($wantedSkillId, fn (Builder $q) => $q->whereHas('offeredSkills', fn (Builder $q) => $q->where('skills.id', $wantedSkillId)))
            ->when($offeredSkillId, fn (Builder $q) => $q->whereHas('wantedSkills', fn (Builder $q) => $q->where('skills.id', $offeredSkillId)))
            ->when($search !== '', fn (Builder $q) => $q->where('name', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->with([
                'offeredSkills' => fn ($q) => $q->orderBy('name'),
                'wantedSkills' => fn ($q) => $q->orderBy('name'),
            ]);

        $this->orderBestMatchesFirst($query, $myWantedIds, $myOfferedIds);

        $people = $query->orderBy('name')->paginate(12)->withQueryString();

        // For each person, work out the swap we would suggest: a skill they teach that I want,
        // and a skill they want that I teach. Explicit filters take priority.
        $suggestions = [];
        foreach ($people as $person) {
            $theyTeach = $person->offeredSkills->pluck('id')->map(fn ($id) => (int) $id)->all();
            $theyWant = $person->wantedSkills->pluck('id')->map(fn ($id) => (int) $id)->all();

            $canLearn = array_values(array_intersect($theyTeach, $myWantedIds));
            $canTeach = array_values(array_intersect($theyWant, $myOfferedIds));

            $learn = $wantedSkillId ?? ($canLearn[0] ?? null);
            $teach = $offeredSkillId ?? ($canTeach[0] ?? null);

            $suggestions[$person->id] = [
                'canLearn' => $canLearn,
                'canTeach' => $canTeach,
                'isMutual' => $canLearn !== [] && $canTeach !== [],
                'learn' => $learn !== $teach ? $learn : null,
                'teach' => $learn !== $teach ? $teach : null,
            ];
        }

        $pendingPairs = $user->sentSwapRequests()
            ->where('status', 'pending')
            ->get(['receiver_id', 'offered_skill_id', 'requested_skill_id'])
            ->map(fn ($r) => "{$r->receiver_id}:{$r->offered_skill_id}:{$r->requested_skill_id}")
            ->all();

        return view('matches.index', [
            'people' => $people,
            'suggestions' => $suggestions,
            'allSkills' => Skill::orderBy('name')->get(),
            'wantedSkillId' => $wantedSkillId,
            'offeredSkillId' => $offeredSkillId,
            'search' => $search,
            'pendingPairs' => $pendingPairs,
            'hasSkills' => $myOfferedIds !== [] || $myWantedIds !== [],
            'isFiltered' => $wantedSkillId || $offeredSkillId || $search !== '',
        ]);
    }

    /**
     * Sort people who both teach something I want and want something I teach to the top,
     * then people who match on one side only.
     *
     * @param  Builder<User>  $query
     * @param  list<int>  $myWantedIds
     * @param  list<int>  $myOfferedIds
     */
    private function orderBestMatchesFirst(Builder $query, array $myWantedIds, array $myOfferedIds): void
    {
        $existsSql = function (string $type, array $skillIds): array {
            if ($skillIds === []) {
                return ['0', []];
            }

            $placeholders = implode(',', array_fill(0, count($skillIds), '?'));

            return [
                "exists (select 1 from user_skills where user_skills.user_id = users.id and user_skills.type = ? and user_skills.skill_id in ($placeholders))",
                [$type, ...$skillIds],
            ];
        };

        [$teachesMeSql, $teachesMeBindings] = $existsSql('offered', $myWantedIds);
        [$wantsMineSql, $wantsMineBindings] = $existsSql('wanted', $myOfferedIds);

        $query->orderByRaw(
            "(case when ($teachesMeSql) and ($wantsMineSql) then 2 when ($teachesMeSql) or ($wantsMineSql) then 1 else 0 end) desc",
            [...$teachesMeBindings, ...$wantsMineBindings, ...$teachesMeBindings, ...$wantsMineBindings],
        );
    }
}
