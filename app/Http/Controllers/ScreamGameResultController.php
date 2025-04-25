<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ScreamGameResultController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'scream_file' => 'required|file|mimetypes:video/*|max:20000',
        ],
            [
                'scream_file.max' => 'Scream file must be less than 20MB.',
            ]
        );

        $media = $this->createMedia($request->file('scream_file'));

        if (empty($media)) {
            return redirect()->back()->with('error', 'There was an error uploading your scream video. Try again?');
        }

        return redirect()->back()->with('success', 'Scream result processed.');
    }

    // TODO: this should probably be a Media method... createFromUpload or something.
    protected function createMedia(UploadedFile $file): ?Media
    {
        try {
            $path = $file->store('screams');

            return Media::create([
                'disk' => config('filesystems.default'),
                'path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating scream game media item: '.$e->getMessage());

            return null;
        }
    }

    // TODO: finish writing this.
    protected function analyzeMedia(Media|RedirectResponse $media)
    {
        try {
            $loudness = $this->calculateLoudness($media);

            $phraseWasSpoken = $this->parsePhrase($media);

            $wasPerformedInPublic = $this->identifyIfPublic($media);
        } catch (\Exception $e) {
            Log::error('Error analyzing scream game media: '.$e->getMessage());

            return redirect()->back()->with('error', 'There was an error analyzing your scream video. Try again?');
        }
    }
}
