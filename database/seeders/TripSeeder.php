<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Location;
use App\Models\Trip;
use App\Services\DestinationPicker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use RuntimeException;

class TripSeeder extends Seeder
{
    /**
     * How far back the walking man's history reaches.
     */
    private const YEARS_OF_HISTORY = 2;

    /**
     * Walks a chain of trips from two years ago up to the one still underway.
     * Nothing here is invented: stops are real cities and distances are really
     * walked. With `app.seed_geocoding` on it goes further and geocodes a street
     * address and a driving distance for every stop, the way `trip:next` does,
     * at the cost of a couple of API calls per trip.
     *
     * How many trips that takes scales with the walking speed, since a faster
     * walker finishes each leg sooner and needs more of them to span the years.
     */
    public function run(DestinationPicker $picker): void
    {
        // Two, not one: every destination excludes the city it departs from, so
        // a lone city leaves the walking man with nowhere to go.
        if (City::query()->count() < 2) {
            throw new RuntimeException('Seed at least two cities first: php artisan db:seed --class='.CitySeeder::class);
        }

        $pick = config('app.seed_geocoding')
            ? fn (?Location $from = null) => $picker->pick($from)
            : fn (?Location $from = null) => $picker->pickCityCenter($from);

        $departure = Carbon::now()->subYears(self::YEARS_OF_HISTORY);
        $origin = $pick()->location;

        do {
            $destination = $pick($origin);

            $distance = $destination->drivingDistanceMiles
                ?? Trip::calculateDistance($origin, $destination->location);

            $arrival = Trip::arrivalAfterWalking($distance, $departure);

            if ($arrival->lessThanOrEqualTo($departure)) {
                // Nothing below advances the clock on its own, so a trip that
                // takes no time would leave this looping on the database.
                throw new RuntimeException('A trip covering '.$distance.' miles took no time to walk.');
            }

            Trip::create([
                'origin_location_id' => $origin->id,
                'destination_location_id' => $destination->location->id,
                'distance' => $distance,
                'departure' => $departure,
                'arrival' => $arrival,
                'destination_from_user' => false,
                'destination_is_random' => true,
            ]);

            $origin = $destination->location;
            $departure = $arrival;
        } while ($arrival->isPast());
    }
}
