<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    /**
     * Seed a starter catalogue of skills. Safe to run more than once.
     */
    public function run(): void
    {
        $catalogue = [
            'Programming' => ['PHP', 'Laravel', 'JavaScript', 'Python', 'SQL', 'React'],
            'Design' => ['Figma', 'Photoshop', 'UI/UX Design', 'Illustration'],
            'Languages' => ['English', 'Spanish', 'German', 'Russian', 'Turkish'],
            'Music' => ['Guitar', 'Piano', 'Singing'],
            'Business' => ['Marketing', 'Public Speaking', 'Excel'],
            'Lifestyle' => ['Cooking', 'Photography', 'Fitness'],
        ];

        foreach ($catalogue as $category => $names) {
            foreach ($names as $name) {
                if (Skill::where('name', $name)->exists()) {
                    continue;
                }

                Skill::create([
                    'name' => $name,
                    'slug' => Skill::uniqueSlug($name),
                    'category' => $category,
                ]);
            }
        }
    }
}
