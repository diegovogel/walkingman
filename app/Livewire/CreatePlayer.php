<?php

namespace App\Livewire;

use App\Models\Player;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CreatePlayer extends Component
{
    public $playerName;

    /**
     * Validates and creates a new player record in the database.
     * Additionally, stores the player's name in a cookie for one year.
     *
     * @return bool Returns true on successful creation, or false if an error occurs.
     */
    public function createPlayer(): bool
    {
        dump('createPlayer');
        $this->validate();

        try {
            $player = Player::create([
                'name' => $this->playerName,
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating player: '.$e->getMessage());

            return false;
        }

        cookie()->queue('player', $player->name, 60 * 24 * 365);

        return true;
    }

    protected function rules()
    {
        return [
            'playerName' => 'required|unique:players,name|max:30|regex:/^[A-Za-z0-9_-]+$/',
        ];
    }

    public function render()
    {
        return view('livewire.create-player');
    }
}
