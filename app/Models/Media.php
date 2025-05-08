<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
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

    /**
     * Create a media record from the uploaded file.
     *
     * @param  UploadedFile  $file  The uploaded file instance.
     * @param  string  $storeDirectory  The directory where the file should be stored.
     * @param  string  $disk  The filesystem disk to store the file. Defaults to the application's default disk.
     * @return Media|null Returns the created media record or null if an error occurs.
     */
    public static function createFromUploadedFile(UploadedFile $file, string $storeDirectory = '', string $disk = ''): ?Media
    {
        $disk = $disk ?: config('filesystems.default');

        try {
            $path = $file->store($storeDirectory, $disk);

            return Media::create([
                'disk' => $disk,
                'path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating media item: '.$e->getMessage());

            return null;
        }
    }
}
