<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Player;

class GameController extends Controller
{
    public function index() {}

    public function show(Game $game)
    {
        return view('game', [
            'game' => $game,
            'okToPlay' => $this->okToPlay(),
            'gameComponentName' => "games.{$game->handle}",
        ]);
    }

    /**
     * Determines if conditions are met to allow the user to play a game.
     *
     * @return bool Returns true if all conditions are met, or false if not.
     */
    public function okToPlay(): bool
    {
        $user = auth()->user();
        $player = $user?->player;

        if (empty($player)) {
            $playerName = request()->cookie('player');
            $player = Player::findByName($playerName);
        }

        return ! empty($player);
    }
}
