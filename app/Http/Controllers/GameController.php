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
        // First try to get the player from the user.
        $user = auth()->user();
        $player = $user?->player;

        // If there's no user player, check for a player cookie.
        if (empty($player)) {
            $playerName = request()->cookie('player');
            $player = Player::findByName($playerName);

            if (empty($player)) {
                cookie()->queue('player', null, -1);
            }
        }

        return ! empty($player);
    }
}
