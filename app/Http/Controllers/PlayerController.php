<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PlayerController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'player_name' => 'required|unique:players,name|max:30|regex:/^[A-Za-z0-9_-]+$/',
        ]);

        try {
            $player = Player::create([
                'name' => $request->player_name,
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating player: '.$e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'Error creating player. Try again?')
                ->withInput();
        }

        cookie()->queue('player', $player->name, 60 * 24 * 365);

        return to_route('view-player-form')->with('success', 'Player created!');
    }
}
