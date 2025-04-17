<?php

use App\Enums\GameStatus;
use App\Models\Game;
use Database\Seeders\GameSeeder;

beforeEach(function () {
    $this->gameSeeder = Mockery::mock(GameSeeder::class)->makePartial();

    $this->setDirectory = function (string $directory): void {
        $this->gameSeeder->shouldAllowMockingProtectedMethods()
            ->shouldReceive('getDirectoryPath')
            ->andReturn($directory);
    };
});

it('should add a game to the database for each game file', function () {
    ($this->setDirectory)(__DIR__.'/../../fixtures/games/valid');

    $this->gameSeeder->run();

    expect(Game::count())->toBe(2);
});

it('should only create games from Markdown files', function () {
    ($this->setDirectory)(__DIR__.'/../../fixtures/games/valid');

    $this->gameSeeder->run();

    $jsonGame = Game::where('name', 'JSON Game')->first();

    expect(Game::count())->toBe(2)
        ->and($jsonGame)->toBeNull();
});

it('should log an error if no game files are found', function () {
    ($this->setDirectory)(__DIR__.'/../../fixtures/games/no-game-files');

    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn ($message) => Str::contains($message, 'no game files found'));

    $this->gameSeeder->run();
});

it('should skip a game if there is a problem getting its info', function () {
    ($this->setDirectory)(__DIR__.'/../../fixtures/games/some-invalid');

    $this->gameSeeder->run();

    expect(Game::count())->toBe(2);
});

it('should set the game status to "draft" if the given status is invalid or missing', function () {
    ($this->setDirectory)(__DIR__.'/../../fixtures/games/invalid-and-missing-statuses');

    $this->gameSeeder->run();

    $publishedGames = Game::where('status', GameStatus::PUBLISHED);
    $draftGames = Game::where('status', GameStatus::DRAFT);

    expect($publishedGames->count())->toBe(1)
        ->and($draftGames->count())->toBe(2);
});
