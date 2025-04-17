<?php

namespace App\Http\Controllers;

use App\Http\Resources\ScreamGameResultResource;
use App\Models\ScreamGameResult;
use Illuminate\Http\Request;

class ScreamGameResultController extends Controller
{
    public function index()
    {
        return ScreamGameResultResource::collection(ScreamGameResult::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'media_id' => ['nullable'],
            'loudness' => ['required', 'integer'],
            'phrase_was_spoken' => ['boolean'],
            'performed_in_public' => ['boolean'],
        ]);

        return new ScreamGameResultResource(ScreamGameResult::create($data));
    }

    public function show(ScreamGameResult $screamGameResult)
    {
        return new ScreamGameResultResource($screamGameResult);
    }

    public function update(Request $request, ScreamGameResult $screamGameResult)
    {
        $data = $request->validate([
            'media_id' => ['nullable'],
            'loudness' => ['required', 'integer'],
            'phrase_was_spoken' => ['boolean'],
            'performed_in_public' => ['boolean'],
        ]);

        $screamGameResult->update($data);

        return new ScreamGameResultResource($screamGameResult);
    }

    public function destroy(ScreamGameResult $screamGameResult)
    {
        $screamGameResult->delete();

        return response()->json();
    }
}
