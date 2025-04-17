<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScreamGameResult extends Model
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
}
