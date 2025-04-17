<?php

use App\Models\Game;
use App\Models\GameResult;
use App\Models\Player;
use Database\Seeders\GameSeeder;

beforeEach(function () {
    $gameSeeder = new GameSeeder;
    $gameSeeder->run();

    // Set all games to draft status.
    $games = Game::all();
    foreach ($games as $game) {
        $game->status = 'draft';
        $game->save();
    }

    // Set the first game to published status.
    $this->publishedGame = $games->first();
    $this->publishedGame->status = 'published';
    $this->publishedGame->save();

    Player::factory()->count(10)->create();
});

it('should not create results for draft games', function () {
    // Create 100 game results.
    GameResult::factory()->count(100)->create();

    // Check that all of the results are for the published game.
    $resultsForPublishedGame = GameResult::where('game_id', $this->publishedGame->id)->get();
    expect($resultsForPublishedGame->count())->toBe(100);
});

it('should create results with a resultable_type that matches the result_model of the associated game', function () {
    $gameResult = GameResult::factory()->create();
    $game = Game::find($gameResult->game_id);

    expect($gameResult->resultable_type)->toBe($game->result_model);
});

it('should create results with a score that matches the calculated score from the game-specific result', function () {
    $gameResult = GameResult::factory()->create();
    $specificGameResult = $gameResult->resultable;

    expect($gameResult->score)->toBe($specificGameResult->calculateScore());
});
