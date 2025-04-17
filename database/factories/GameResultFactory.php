<?php

namespace Database\Factories;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\GameResult;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class GameResultFactory extends Factory
{
    protected $model = GameResult::class;

    public function definition(): array
    {
        $publishedGames = Game::where('status', GameStatus::PUBLISHED)->get();
        $game = $publishedGames->random();

        $gameResultModel = $game->result_model;
        $specificGameResult = $gameResultModel::factory()->create();

        return [
            'resultable_type' => $game->result_model,
            'resultable_id' => $specificGameResult->id,
            'score' => $specificGameResult->calculateScore(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'game_id' => $game->id,
        ];
    }
}
