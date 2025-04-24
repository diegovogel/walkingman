<?php

namespace App\Http\Controllers;

use App\Models\Game;

class GameController extends Controller
{
    public function index() {}

    public function show(Game $game)
    {
        return view('game', ['game' => $game]);
    }
}
