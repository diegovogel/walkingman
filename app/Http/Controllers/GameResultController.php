<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameResultResource;
use App\Models\GameResult;
use Illuminate\Http\Request;

class GameResultController extends Controller
{
    public function index()
    {
        return GameResultResource::collection(GameResult::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'player_id' => ['required', 'exists:players'],
            'game_id' => ['nullable', 'exists:games'],
            'resultable_type' => ['required'],
            'resultable_id' => ['required'],
            'score' => ['required', 'integer'],
        ]);

        return new GameResultResource(GameResult::create($data));
    }

    public function show(GameResult $gameResult)
    {
        return new GameResultResource($gameResult);
    }

    public function update(Request $request, GameResult $gameResult)
    {
        $data = $request->validate([
            'player_id' => ['required', 'exists:players'],
            'game_id' => ['nullable', 'exists:games'],
            'resultable_type' => ['required'],
            'resultable_id' => ['required'],
            'score' => ['required', 'integer'],
        ]);

        $gameResult->update($data);

        return new GameResultResource($gameResult);
    }

    public function destroy(GameResult $gameResult)
    {
        $gameResult->delete();

        return response()->json();
    }
}
