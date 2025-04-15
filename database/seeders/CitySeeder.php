<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;
use Log;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $filePath = database_path('data/cities.json');

        if (! file_exists($filePath)) {
            Log::error("CitySeeder: file does not exist: {$filePath}");

            return;
        }

        $json = file_get_contents($filePath);
        $cities = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('CitySeeder: json_decode error: '.json_last_error_msg());

            return;
        }

        if (! $cities) {
            Log::warning('CitySeeder: no cities found');

            return;
        }

        $cityData = collect($cities)->map(function ($city) {
            return [
                'name' => $city['city'],
                'state_abbreviation' => $city['state_id'],
                'state_name' => $city['state_name'],
                'latitude' => $city['lat'],
                'longitude' => $city['lng'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        City::insert($cityData);

        Log::info('CitySeeder: '.count($cityData).' cities inserted');
    }
}
