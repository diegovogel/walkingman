<?php

namespace App\View\Components\Games;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Scream extends Component
{
    public function render(): View
    {
        return view('components.games.scream');
    }
}
