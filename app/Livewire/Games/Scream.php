<?php

namespace App\Livewire\Games;

use App\Models\Player;
use App\Models\User;
use Livewire\Component;

class Scream extends Component
{
    public ?User $user;

    public ?Player $player;

    public function mount()
    {
        $this->user = auth()->user();
        $this->player = $this->user?->player;

        if (empty($this->player)) {
            $playerName = request()->cookie('player');
            $this->player = Player::findByName($playerName);
        }
    }

    public function render()
    {
        return view('livewire.games.scream');
    }
}
