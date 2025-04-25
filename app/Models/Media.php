<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'size',
    ];

    /**
     * Generate the URL for the stored file.
     */
    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
