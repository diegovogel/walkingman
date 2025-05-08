<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $media = Media::createFromUploadedFile($request->file('scream_file'), 'screams');

        if (empty($media)) {
            return redirect()->back()->with('error', 'There was an error uploading your scream video. Try again?');
        }

        return redirect()->back()->with('success', 'Scream result processed.');
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
