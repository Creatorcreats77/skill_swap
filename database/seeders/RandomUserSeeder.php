<?php

namespace Database\Seeders;

use App\Http\Controllers\UserSkillController;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\Concerns\DownloadsAvatars;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Downloads random people (name, photo, phone) from https://randomuser.me and gives
 * each of them a few random skills to teach and to learn.
 *
 * The API is called with a fixed seed, so every run returns the same people and the
 * seeder can safely run again. Accounts use emails like "bigswan218@skillswap.test"
 * and the password "password". Without internet access it prints a warning and skips.
 */
class RandomUserSeeder extends Seeder
{
    use DownloadsAvatars;

    public const COUNT = 24;

    private const API_URL = 'https://randomuser.me/api/';

    public function run(): void
    {
        $people = $this->fetchPeople();

        if ($people === []) {
            return;
        }

        $skills = Skill::orderBy('id')->get(['id', 'name']);

        foreach ($people as $person) {
            $user = $this->seedUser($person, $skills);
            $this->downloadAvatar($user, $person['picture']['large']);
        }

        $this->command?->info('  Added '.count($people).' people from randomuser.me.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchPeople(): array
    {
        try {
            $response = Http::timeout(20)->retry(2, 500, throw: false)->get(self::API_URL, [
                'results' => self::COUNT,
                'seed' => 'skillswap',
                'nat' => 'us,gb,ca,au,de,fr,es,nl,tr',
                'inc' => 'gender,name,login,phone,picture',
                'noinfo' => '',
            ]);

            if ($response->successful()) {
                return $response->json('results') ?? [];
            }

            $this->command?->warn("  randomuser.me returned HTTP {$response->status()}; skipping random people.");
        } catch (Throwable $e) {
            $this->command?->warn("  Could not reach randomuser.me ({$e->getMessage()}); skipping random people.");
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $person
     * @param  Collection<int, Skill>  $skills
     */
    private function seedUser(array $person, Collection $skills): User
    {
        $email = strtolower($person['login']['username']).'@skillswap.test';
        $name = $person['name']['first'].' '.$person['name']['last'];

        // Stable per-person "shuffle", so each person keeps the same skills on every run.
        $seed = crc32($email);
        $shuffled = $skills->sortBy(fn (Skill $skill) => crc32($email.'#'.$skill->id))->values();
        $teaches = $shuffled->take(1 + $seed % 3);
        $wants = $shuffled->slice($teaches->count())->take(1 + intdiv($seed, 3) % 3);

        $user = User::firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'bio' => 'Can teach '.$teaches->pluck('name')->join(', ', ' and ')
                .'. Looking to learn '.$wants->pluck('name')->join(', ', ' and ').'.',
            'phone' => $person['phone'] ?? null,
            'show_email' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);

        if (! $user->exists) {
            $user->password = Hash::make('password');
        }

        $user->save();

        $levels = UserSkillController::PROFICIENCIES;
        $user->offeredSkills()->sync(
            $teaches->mapWithKeys(fn (Skill $skill) => [
                $skill->id => ['proficiency' => $levels[($seed + $skill->id) % count($levels)]],
            ])->all()
        );
        $user->wantedSkills()->sync($wants->pluck('id')->all());

        return $user;
    }
}
