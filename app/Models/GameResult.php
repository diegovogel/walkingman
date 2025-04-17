<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GameResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'game_id',
        'resultable_type',
        'resultable_id',
        'score',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the game-specific result (e.g. TossGameResult, ScreamGameResult).
     */
    public function resultable(): MorphTo
    {
        return $this->morphTo();
    }
}
