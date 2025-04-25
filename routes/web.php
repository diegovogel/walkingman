<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ScreamGameResultController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

Route::get('games/{game:handle}', [GameController::class, 'show'])->name('game');

Route::view('player/new', 'create-player')->name('view-player-form');
Route::post('player', [PlayerController::class, 'store'])->name('submit-player-form');

Route::post('games/scream', [ScreamGameResultController::class, 'store'])->name('submit-scream-game-result');

Route::get('phpmyinfo', function () {
    phpinfo();
})->name('phpmyinfo');

require __DIR__.'/auth.php';
