<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameResult;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class GameResultFactory extends Factory
{
    protected $model = GameResult::class;

    public function definition(): array
    {
        return [
            'resultable_type' => $this->faker->word(),
            'resultable_id' => $this->faker->word(),
            'score' => $this->faker->randomNumber(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'player_id' => Player::factory(),
            'game_id' => Game::factory(),
        ];
    }
}
