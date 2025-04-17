<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\GameResult;
use App\Models\Player;
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
        // Create users.
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();
            Player::factory()->create(['user_id' => $user->id]);
        }

        // Create cities.
        $this->call([
            CitySeeder::class,
        ]);

        // Create trips.
        for ($i = 0; $i < 30; $i++) {
            Trip::factory()->create();
        }

        // Create anonymous players.
        Player::factory(10)->create();

        // Create games.
        $this->call([
            GameSeeder::class,
        ]);

        // Create game results. We create an average of 3 results per player.
        $playerCount = Player::count();
        GameResult::factory($playerCount * 3)->create();
    }
}
