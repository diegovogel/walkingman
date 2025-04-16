<?php

namespace Database\Seeders;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10)->create();

        $this->call([
            CitySeeder::class,
        ]);

        for ($i = 0; $i < 30; $i++) {
            Trip::factory()->create();
        }
    }
}
