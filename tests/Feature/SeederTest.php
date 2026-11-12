<?php

use App\Models\Message;
use App\Models\Skill;
use App\Models\SwapRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * @return array<string, mixed>
 */
function fakeRandomUser(string $username, string $first, string $last, string $gender): array
{
    return [
        'gender' => $gender,
        'name' => ['title' => '', 'first' => $first, 'last' => $last],
        'login' => ['username' => $username],
        'phone' => '555-0100',
        'picture' => ['large' => "https://randomuser.me/api/portraits/{$gender}/1.jpg"],
    ];
}

beforeEach(function () {
    Storage::fake('public');
    Http::preventStrayRequests();
    Http::fake([
        'randomuser.me/api/portraits/*' => Http::response('fake-jpeg-bytes', 200, ['Content-Type' => 'image/jpeg']),
        'randomuser.me/api/*' => Http::response(['results' => [
            fakeRandomUser('bigswan218', 'Neea', 'Juntunen', 'women'),
            fakeRandomUser('yellowgorilla990', 'Jone', 'Oie', 'men'),
        ]]),
    ]);
});

test('the database seeder creates demo data and can run twice', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $testUser = User::where('email', 'test@example.com')->firstOrFail();

    expect(Skill::count())->toBe(24)
        ->and(User::count())->toBe(15) // 13 demo people + 2 from randomuser.me
        ->and(SwapRequest::count())->toBe(6)
        ->and(Message::count())->toBe(13) // 6 request notes + 7 replies
        ->and(Message::whereNull('conversation_id')->count())->toBe(0)
        ->and($testUser->offeredSkills()->count())->toBe(3)
        ->and($testUser->wantedSkills()->count())->toBe(3);
});

test('seeded people get downloaded photos and random people get skills', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::whereNull('avatar')->count())->toBe(0);
    User::all()->each(fn (User $user) => Storage::disk('public')->assertExists($user->avatar));

    $neea = User::where('email', 'bigswan218@skillswap.test')->firstOrFail();
    expect($neea->name)->toBe('Neea Juntunen')
        ->and($neea->offeredSkills()->count())->toBeGreaterThan(0)
        ->and($neea->wantedSkills()->count())->toBeGreaterThan(0)
        ->and($neea->offeredSkills()->pluck('skills.id')->intersect($neea->wantedSkills()->pluck('skills.id')))->toBeEmpty();
});

test('seeding still works without internet, just without photos or random people', function () {
    // Start from a clean HTTP factory so the beforeEach stubs don't take priority.
    Http::swap(new HttpFactory);
    Http::preventStrayRequests();
    Http::fake(['*' => Http::response(null, 503)]);

    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(13)
        ->and(User::whereNotNull('avatar')->count())->toBe(0);
});

test('every seeded swap request is a real match', function () {
    $this->seed(DatabaseSeeder::class);

    SwapRequest::all()->each(function (SwapRequest $swap) {
        $isMatch = User::whereKey($swap->receiver_id)
            ->matching($swap->requested_skill_id, $swap->offered_skill_id)
            ->exists();

        expect($isMatch)->toBeTrue("Swap request #{$swap->id} is not a valid match");
        expect($swap->sender->offeredSkills()->whereKey($swap->offered_skill_id)->exists())->toBeTrue();
    });
});

test('the test user sees perfect matches after seeding', function () {
    $this->seed(DatabaseSeeder::class);

    $this->actingAs(User::where('email', 'test@example.com')->first())
        ->get(route('matches.index'))
        ->assertOk()
        ->assertSeeInOrder(['Aylar Myradova', 'Carlos Mendez', 'Mia Chen'])
        ->assertSee('Perfect match for you');
});
