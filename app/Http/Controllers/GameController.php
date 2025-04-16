<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameResource;
use App\Models\Game;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function index()
    {
        return GameResource::collection(Game::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required'],
            'short_description' => ['required'],
            'max_possible_points' => ['required'],
            'path_to_description' => ['nullable'],
        ]);

        return new GameResource(Game::create($data));
    }

    public function show(Game $game)
    {
        return new GameResource($game);
    }

    public function update(Request $request, Game $game)
    {
        $data = $request->validate([
            'title' => ['required'],
            'short_description' => ['required'],
            'max_possible_points' => ['required'],
            'path_to_description' => ['nullable'],
        ]);

        $game->update($data);

        return new GameResource($game);
    }

    public function destroy(Game $game)
    {
        $game->delete();

        return response()->json();
    }
}
