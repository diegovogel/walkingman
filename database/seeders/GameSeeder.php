<?php

namespace Database\Seeders;

use App\Enums\GameStatus;
use App\Models\Game;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelMarkdown\MarkdownRenderer;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        // Get the game info files.
        $gameFilePaths = glob(static::getDirectoryPath().'/*.md');

        if (empty($gameFilePaths)) {
            Log::warning('GameSeeder: no game files found in '.$this->getDirectoryPath());

            return;
        }

        // Get game info from each file.
        $gamesData = collect($gameFilePaths)
            ->map(fn ($filePath) => $this->getGameInfoFromFile($filePath))
            ->filter()
            ->toArray();

        Game::insert($gamesData);

        Log::info('GameSeeder: '.count($gamesData).' games inserted');
    }

    protected function getDirectoryPath(): string
    {
        return database_path('data/games');
    }

    /**
     * Retrieves game information from a given file.
     *
     * This method parses the contents of a Markdown file with YAML front matter to extract game details. If an error occurs during file reading or processing, it handles the exception by logging the error and returns an empty array.
     *
     * @param  string  $filePath  The path to the Markdown file containing the game information.
     * @return array An associative array containing the game's information. Returns an empty array on failure.
     */
    protected function getGameInfoFromFile(string $filePath): array
    {
        try {
            $data = YamlFrontMatter::parseFile($filePath);

            $longDescriptionMarkdown = $data->body();
            $renderer = new MarkdownRenderer;
            $longDescriptionHtml = $renderer->toHtml($longDescriptionMarkdown);
        } catch (\Throwable $e) {
            Log::error('GameSeeder: error getting game info from '.$filePath.': '.$e->getMessage());

            return [];
        }

        // Get the game status or default to draft.
        $statusData = $data->matter('status');
        $status = GameStatus::tryFrom($statusData)?->value;
        if (empty($status)) {
            $status = GameStatus::DRAFT;
        }

        return [
            'title' => $data->matter('title'),
            'short_description' => $data->matter('short_description'),
            'max_possible_points' => $data->matter('max_possible_points'),
            'long_description' => $longDescriptionHtml,
            'status' => $status,
            'handle' => $data->matter('handle'),
            'result_model' => $data->matter('result_model'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
