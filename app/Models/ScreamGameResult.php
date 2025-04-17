<?php

namespace App\Models;

use App\Interfaces\GameResultInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScreamGameResult extends Model implements GameResultInterface
{
    use HasFactory;

    protected $fillable = [
        'media_id',
        'loudness',
        'phrase_was_spoken',
        'performed_in_public',
    ];

    protected function casts(): array
    {
        return [
            'phrase_was_spoken' => 'boolean',
            'performed_in_public' => 'boolean',
        ];
    }

    /**
     * Calculate the score for this game result based on its properties.
     */
    public function calculateScore(): int
    {
        $score = 0;

        // Base score from loudness.
        $score += $this->loudness;

        // Bonus for speaking the phrase.
        if ($this->phrase_was_spoken) {
            $score += 20;
        }

        // Bonus for performing in public.
        if ($this->performed_in_public) {
            $score += 40;
        }

        return $score;
    }
}
