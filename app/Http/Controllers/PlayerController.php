<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlayerResource;
use App\Models\Player;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    public function index()
    {
        return PlayerResource::collection(Player::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users'],
            'name' => ['required'],
            'current_score' => ['required', 'integer'],
        ]);

        return new PlayerResource(Player::create($data));
    }

    public function show(Player $player)
    {
        return new PlayerResource($player);
    }

    public function update(Request $request, Player $player)
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users'],
            'name' => ['required'],
            'current_score' => ['required', 'integer'],
        ]);

        $player->update($data);

        return new PlayerResource($player);
    }

    public function destroy(Player $player)
    {
        $player->delete();

        return response()->json();
    }
}
