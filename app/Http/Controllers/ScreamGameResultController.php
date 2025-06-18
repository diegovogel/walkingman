<?php

namespace App\Http\Controllers;

use App\Models\ScreamGameResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        $gameResult = new ScreamGameResult;

        $gameResult->createMedia($request->file('scream_file'));

        if (empty($gameResult->media)) {
            return redirect()->back()->with('error', 'There was an error uploading your scream video. Maybe try again?');
        }

        if ($gameResult->analyzeMedia()) {
            return redirect()->back()->with('success', 'Scream result processed.');
        } else {
            return redirect()->back()->with('error', 'There was an error analyzing your scream video. Maybe try again?');
        }
    }
}
