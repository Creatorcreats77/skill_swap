<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Log in as test@example.com / password to see the demo data.
     */
    public function run(): void
    {
        $this->call([
            SkillSeeder::class,
            UserSeeder::class,
            RandomUserSeeder::class,
        ]);
    }
}
