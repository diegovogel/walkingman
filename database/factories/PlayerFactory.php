<?php

namespace Database\Factories;

use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PlayerFactory extends Factory
{
    protected $model = Player::class;

    public function definition(): array
    {
        $nameWordsArray = $this->faker->words(2);
        $playerName = implode('_', $nameWordsArray);

        return [
            'name' => $playerName,
            'current_score' => $this->faker->randomNumber(6),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
