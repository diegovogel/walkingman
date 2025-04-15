<?php

namespace App\Http\Controllers;

use App\Http\Resources\CityResource;
use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index()
    {
        return CityResource::collection(City::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required'],
            'state_abbreviation' => ['required'],
            'state_name' => ['required'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        return new CityResource(City::create($data));
    }

    public function show(City $city)
    {
        return new CityResource($city);
    }

    public function update(Request $request, City $city)
    {
        $data = $request->validate([
            'name' => ['required'],
            'state_abbreviation' => ['required'],
            'state_name' => ['required'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        $city->update($data);

        return new CityResource($city);
    }

    public function destroy(City $city)
    {
        $city->delete();

        return response()->json();
    }
}
