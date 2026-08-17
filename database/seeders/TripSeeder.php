<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Trip;
use Illuminate\Database\Seeder;
use RuntimeException;

class TripSeeder extends Seeder
{
    /**
     * Walks the chain from two years back up to the one still underway. How many
     * trips that takes scales with the walking speed, since a faster walker
     * finishes each leg sooner and needs more of them to cover the two years.
     */
    public function run(): void
    {
        if (City::query()->doesntExist()) {
            // The factory would quietly invent cities of its own, and a walking
            // man wandering between places Faker made up is worse than a stop.
            throw new RuntimeException('Seed cities first: php artisan db:seed --class='.CitySeeder::class);
        }

        $trip = Trip::factory()->departingAt(now()->subYears(2))->create();

        while ($trip->arrivesAt()->isPast()) {
            $trip = Trip::factory()->create();
        }
    }
}
