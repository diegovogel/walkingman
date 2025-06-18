<?php

namespace App\Models;

use App\Interfaces\GameResultInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Http\UploadedFile;

class ScreamGameResult extends Model implements GameResultInterface
{
    use HasFactory;

    protected $fillable = [
        'loudness',
        'phrase_was_spoken',
        'performed_in_public',
    ];

    public function media(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable');
    }

    public function createMedia(UploadedFile $file): void
    {
        $this->media = Media::createFromUploadedFile($file, 'screams');
    }

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

    // TODO: finish writing this.
    public function analyzeMedia(?Media $media = null): bool
    {
        $media = $media ?? $this->media;

        try {
            $this->loudness = $this->calculateLoudness($media);

            $this->phrase_was_spoken = $this->parsePhrase($media);

            $this->performed_in_public = $this->identifyIfPublic($media);
        } catch (\Exception $e) {
            report('Error analyzing scream game media: '.$e->getMessage());

            return false;
        }

        return true;
    }

    protected function calculateLoudness(Media $media): int
    {
        return 0;
    }

    protected function parsePhrase(Media $media): bool
    {
        return false;
    }

    protected function identifyIfPublic(Media $media): bool
    {
        return false;
    }
}
