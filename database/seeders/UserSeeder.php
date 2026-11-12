<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Skill;
use App\Models\SwapRequest;
use App\Models\User;
use Database\Seeders\Concerns\DownloadsAvatars;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Demo users with skills, contacts, swap requests and chat messages.
 *
 * Every account uses the password "password". Safe to run more than once:
 * users are matched by email and their skills are reset to the values below.
 * Profile photos are downloaded from randomuser.me (skipped when offline).
 */
class UserSeeder extends Seeder
{
    use DownloadsAvatars;

    /**
     * @var array<string, int> skill name => id
     */
    private array $skillIds = [];

    public function run(): void
    {
        $this->skillIds = Skill::pluck('id', 'name')->all();

        $users = [];
        foreach ($this->people() as $person) {
            $users[$person['email']] = $this->seedUser($person);
            $this->downloadAvatar($users[$person['email']], "https://randomuser.me/api/portraits/{$person['photo']}.jpg");
        }

        $this->seedSwapRequests($users);
    }

    /**
     * @param  array{name: string, email: string, photo: string, bio: string, phone?: string, telegram?: string, instagram?: string, teaches: array<string, string>, wants: list<string>}  $person
     */
    private function seedUser(array $person): User
    {
        $user = User::firstOrNew(['email' => $person['email']]);

        $user->forceFill([
            'name' => $person['name'],
            'bio' => $person['bio'],
            'phone' => $person['phone'] ?? null,
            'telegram' => $person['telegram'] ?? null,
            'instagram' => $person['instagram'] ?? null,
            'show_email' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);

        if (! $user->exists) {
            $user->password = Hash::make('password');
        }

        $user->save();

        $offered = [];
        foreach ($person['teaches'] as $skillName => $level) {
            $offered[$this->skillId($skillName)] = ['proficiency' => $level];
        }

        $user->offeredSkills()->sync($offered);
        $user->wantedSkills()->sync(array_map(fn (string $name) => $this->skillId($name), $person['wants']));

        return $user;
    }

    /**
     * @param  array<string, User>  $users
     */
    private function seedSwapRequests(array $users): void
    {
        $requests = [
            [
                'from' => 'aylar@skillswap.test', 'to' => 'test@example.com',
                'teaches' => 'Guitar', 'learns' => 'PHP', 'status' => 'pending',
                'note' => 'Hi! I saw you know PHP. I can teach you guitar from zero — chords, strumming, first songs.',
                'messages' => [],
            ],
            [
                'from' => 'test@example.com', 'to' => 'carlos@skillswap.test',
                'teaches' => 'English', 'learns' => 'Spanish', 'status' => 'accepted',
                'note' => 'Hola Carlos! Want to swap English for Spanish?',
                'messages' => [
                    ['carlos@skillswap.test', '¡Claro! Happy to. How about two 1-hour calls a week?'],
                    ['test@example.com', 'Perfect. Tuesday and Thursday evenings work for me.'],
                    ['carlos@skillswap.test', 'Great, I sent you my Telegram. Let\'s start this Tuesday at 19:00.'],
                ],
            ],
            [
                'from' => 'mia@skillswap.test', 'to' => 'test@example.com',
                'teaches' => 'Photography', 'learns' => 'Laravel', 'status' => 'completed',
                'note' => 'I\'d love to build my portfolio site in Laravel. I can teach you photography basics in return.',
                'messages' => [
                    ['test@example.com', 'Your portfolio site is live — congrats!'],
                    ['mia@skillswap.test', 'Thank you so much! And your night shots are getting really good 📸'],
                ],
            ],
            [
                'from' => 'dovlet@skillswap.test', 'to' => 'lena@skillswap.test',
                'teaches' => 'Python', 'learns' => 'German', 'status' => 'accepted',
                'note' => 'Guten Tag! Python for German?',
                'messages' => [
                    ['lena@skillswap.test', 'Ja, gerne! I need help with pandas for my thesis.'],
                    ['dovlet@skillswap.test', 'Deal. I\'ll prepare a short notebook for our first session.'],
                ],
            ],
            [
                'from' => 'sofia@skillswap.test', 'to' => 'arslan@skillswap.test',
                'teaches' => 'Figma', 'learns' => 'Russian', 'status' => 'pending',
                'note' => 'I can teach you Figma from scratch if you help me practice Russian.',
                'messages' => [],
            ],
            [
                'from' => 'merdan@skillswap.test', 'to' => 'carlos@skillswap.test',
                'teaches' => 'Fitness', 'learns' => 'Spanish', 'status' => 'declined',
                'note' => 'Hey, Spanish for gym coaching?',
                'messages' => [],
            ],
        ];

        foreach ($requests as $data) {
            $sender = $users[$data['from']];
            $receiver = $users[$data['to']];

            $swap = SwapRequest::updateOrCreate(
                [
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id,
                    'offered_skill_id' => $this->skillId($data['teaches']),
                    'requested_skill_id' => $this->skillId($data['learns']),
                ],
                [
                    'status' => $data['status'],
                    'initial_message' => $data['note'],
                ],
            );

            if ($swap->messages()->exists()) {
                continue;
            }

            // The request note opens the private chat, followed by the replies.
            $conversation = Conversation::between($sender, $receiver);
            $conversation->send($sender, $data['note'], $swap);

            foreach ($data['messages'] as [$email, $body]) {
                $conversation->send($users[$email], $body, $swap);
            }
        }
    }

    private function skillId(string $name): int
    {
        return $this->skillIds[$name]
            ?? throw new RuntimeException("Skill [{$name}] is missing. Run SkillSeeder first.");
    }

    /**
     * @return list<array{name: string, email: string, photo: string, bio: string, phone?: string, telegram?: string, instagram?: string, teaches: array<string, string>, wants: list<string>}>
     */
    private function people(): array
    {
        return [
            [
                'name' => 'Test User', 'email' => 'test@example.com', 'photo' => 'men/32',
                'bio' => 'Backend developer who wants to finally learn an instrument.',
                'phone' => '+993 61 000001', 'telegram' => 'test_user',
                'teaches' => ['PHP' => 'expert', 'Laravel' => 'intermediate', 'English' => 'expert'],
                'wants' => ['Guitar', 'Spanish', 'Photography'],
            ],
            [
                'name' => 'Aylar Myradova', 'email' => 'aylar@skillswap.test', 'photo' => 'women/44',
                'bio' => 'Music teacher by day, aspiring web developer by night.',
                'phone' => '+993 62 123456', 'telegram' => 'aylar_music', 'instagram' => 'aylar.plays',
                'teaches' => ['Guitar' => 'expert', 'Piano' => 'intermediate'],
                'wants' => ['PHP', 'Laravel'],
            ],
            [
                'name' => 'Carlos Mendez', 'email' => 'carlos@skillswap.test', 'photo' => 'men/45',
                'bio' => 'From Valencia. I cook paella and talk too much — in Spanish.',
                'phone' => '+34 612 345 678', 'telegram' => 'carlos_mendez',
                'teaches' => ['Spanish' => 'expert', 'Cooking' => 'intermediate'],
                'wants' => ['English', 'Python', 'Fitness'],
            ],
            [
                'name' => 'Mia Chen', 'email' => 'mia@skillswap.test', 'photo' => 'women/68',
                'bio' => 'Street and portrait photographer. Building my own portfolio site.',
                'instagram' => 'mia.shoots',
                'teaches' => ['Photography' => 'expert', 'Photoshop' => 'intermediate'],
                'wants' => ['Laravel', 'JavaScript'],
            ],
            [
                'name' => 'Dovlet Atayev', 'email' => 'dovlet@skillswap.test', 'photo' => 'men/52',
                'bio' => 'Data analyst. Python and SQL every day.',
                'phone' => '+993 65 222333', 'telegram' => 'dovlet_data',
                'teaches' => ['Python' => 'expert', 'SQL' => 'expert'],
                'wants' => ['Guitar', 'German'],
            ],
            [
                'name' => 'Lena Fischer', 'email' => 'lena@skillswap.test', 'photo' => 'women/65',
                'bio' => 'Master\'s student from Berlin and part-time fitness coach.',
                'telegram' => 'lena_fischer',
                'teaches' => ['German' => 'expert', 'Fitness' => 'intermediate'],
                'wants' => ['Python', 'Cooking'],
            ],
            [
                'name' => 'Arslan Orazov', 'email' => 'arslan@skillswap.test', 'photo' => 'men/75',
                'bio' => 'Translator. I speak four languages and want to learn design.',
                'phone' => '+993 64 555777',
                'teaches' => ['Russian' => 'expert', 'Turkish' => 'expert'],
                'wants' => ['Figma', 'Marketing'],
            ],
            [
                'name' => 'Sofia Rossi', 'email' => 'sofia@skillswap.test', 'photo' => 'women/90',
                'bio' => 'Product designer. Moving to Moscow next year!',
                'instagram' => 'sofia.designs', 'telegram' => 'sofia_rossi',
                'teaches' => ['Figma' => 'expert', 'UI/UX Design' => 'expert'],
                'wants' => ['Russian', 'Public Speaking'],
            ],
            [
                'name' => 'James Walker', 'email' => 'james@skillswap.test', 'photo' => 'men/86',
                'bio' => 'Marketing lead and Toastmasters member.',
                'phone' => '+44 7700 900123',
                'teaches' => ['Public Speaking' => 'expert', 'Marketing' => 'intermediate'],
                'wants' => ['Turkish', 'Excel'],
            ],
            [
                'name' => 'Selbi Hojayeva', 'email' => 'selbi@skillswap.test', 'photo' => 'women/79',
                'bio' => 'Accountant who loves spreadsheets and karaoke.',
                'telegram' => 'selbi_h',
                'teaches' => ['Excel' => 'expert', 'English' => 'intermediate'],
                'wants' => ['Singing', 'Illustration'],
            ],
            [
                'name' => 'Noah Kim', 'email' => 'noah@skillswap.test', 'photo' => 'men/22',
                'bio' => 'Frontend engineer. React all day, piano at night (badly).',
                'telegram' => 'noahkim_dev',
                'teaches' => ['React' => 'expert', 'JavaScript' => 'expert'],
                'wants' => ['Piano', 'Photography'],
            ],
            [
                'name' => 'Emma Dubois', 'email' => 'emma@skillswap.test', 'photo' => 'women/29',
                'bio' => 'Illustrator and choir singer from Lyon.',
                'instagram' => 'emma.draws',
                'teaches' => ['Singing' => 'intermediate', 'Illustration' => 'expert'],
                'wants' => ['React', 'Fitness'],
            ],
            [
                'name' => 'Merdan Saparov', 'email' => 'merdan@skillswap.test', 'photo' => 'men/61',
                'bio' => 'Chef and personal trainer. Eat well, move more.',
                'phone' => '+993 63 888999',
                'teaches' => ['Cooking' => 'expert', 'Fitness' => 'expert'],
                'wants' => ['Spanish', 'React'],
            ],
        ];
    }
}
