<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PlayerFactory extends Factory
{
    protected $model = Player::class;

    public function definition(): array
    {
        $belongsToUser = $this->faker->boolean();

        if ($belongsToUser) {
            $user = User::inRandomOrder()->first();

            if (! $user) {
                $user = User::factory()->create();
            }
        } else {
            $user = null;
        }

        $nameWordsArray = $this->faker->words(2);
        $playerName = implode('_', $nameWordsArray);

        return [
            'name' => $playerName,
            'current_score' => $this->faker->randomNumber(6),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'user_id' => $user,
        ];
    }
}
