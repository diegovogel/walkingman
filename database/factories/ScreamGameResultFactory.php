<?php

namespace Database\Factories;

use App\Models\ScreamGameResult;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ScreamGameResultFactory extends Factory
{
    protected $model = ScreamGameResult::class;

    public function definition(): array
    {
        return [
            'media_id' => $this->faker->word(),
            'loudness' => $this->faker->randomNumber(),
            'phrase_was_spoken' => $this->faker->boolean(),
            'performed_in_public' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
