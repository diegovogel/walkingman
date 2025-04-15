<?php

namespace App\Http\Controllers;

use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class TripController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Trip::class);

        return TripResource::collection(Trip::all());
    }

    public function store(Request $request)
    {
        $this->authorize('create', Trip::class);

        $data = $request->validate([
            'origin_city_id' => ['nullable', 'exists:cities'],
            'destination_city_id' => ['nullable', 'exists:cities'],
            'distance' => ['required', 'numeric'],
            'departure' => ['required', 'date'],
            'arrival' => ['nullable', 'date'],
            'destination_from_user' => ['boolean'],
            'destination_is_random' => ['boolean'],
            'user_id' => ['nullable', 'exists:users'],
        ]);

        return new TripResource(Trip::create($data));
    }

    public function show(Trip $trip)
    {
        $this->authorize('view', $trip);

        return new TripResource($trip);
    }

    public function update(Request $request, Trip $trip)
    {
        $this->authorize('update', $trip);

        $data = $request->validate([
            'origin_city_id' => ['nullable', 'exists:cities'],
            'destination_city_id' => ['nullable', 'exists:cities'],
            'distance' => ['required', 'numeric'],
            'departure' => ['required', 'date'],
            'arrival' => ['nullable', 'date'],
            'destination_from_user' => ['boolean'],
            'destination_is_random' => ['boolean'],
            'user_id' => ['nullable', 'exists:users'],
        ]);

        $trip->update($data);

        return new TripResource($trip);
    }

    public function destroy(Trip $trip)
    {
        $this->authorize('delete', $trip);

        $trip->delete();

        return response()->json();
    }
}
