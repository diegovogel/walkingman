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

        // Create trips, walking from two years back up to the one still
        // underway. How many that takes depends on the walking speed.
        $trip = Trip::factory()->departingAt(now()->subYears(2))->create();

        while ($trip->arrivesAt()->isPast()) {
            $trip = Trip::factory()->create();
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
