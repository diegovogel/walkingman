<?php

namespace App\Http\Controllers;

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

        return redirect()->back()->with('success', 'Scream uploaded!');
    }
}
