<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'current_score',
    ];

    /**
     * Find a player by name.
     */
    public static function findByName(?string $playerName): ?Player
    {
        if (empty($playerName)) {
            return null;
        }

        return static::where('name', $playerName)->first();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
