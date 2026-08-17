<?php

use App\Models\City;
use App\Models\Location;
use App\Models\Trip;
use App\Services\DestinationPicker;
use App\Services\PickedDestination;
use Database\Seeders\TripSeeder;

use function Pest\Laravel\mock;

/**
 * Stops alternate between Miami and Seattle, roughly 2,700 miles apart, so the
 * two years are covered in a handful of legs rather than hundreds.
 */
function farApartLocation(int $index): Location
{
    [$latitude, $longitude] = $index % 2 === 0 ? [25.77, -80.19] : [47.61, -122.33];

    return Location::factory()->create(['latitude' => $latitude, 'longitude' => $longitude]);
}

/**
 * @param  string  $method  The picker method the seeder is expected to use.
 */
function pickerAlternating(string $method, ?float $drivingDistance = null): void
{
    mock(DestinationPicker::class, function ($mock) use ($method, $drivingDistance) {
        $index = 0;

        $mock->shouldReceive($method)
            ->andReturnUsing(function (?Location $origin = null) use (&$index, $drivingDistance) {
                return new PickedDestination(
                    farApartLocation($index++),
                    $origin === null ? null : $drivingDistance,
                );
            });
    });
}

it('should walk the chain from two years back up to the trip underway', function () {
    config(['app.walking_speed' => 2]);
    City::factory()->create();
    pickerAlternating('pickCityCenter');

    $this->seed(TripSeeder::class);

    expect(Trip::underway()->count())->toBe(1)
        ->and(Trip::completed()->count())->toBeGreaterThan(0)
        ->and(Trip::oldest('departure')->first()->departedAt()->diffInYears(now()))
        ->toBeGreaterThanOrEqual(2);
});

it('should not reach for geocoding unless it is turned on', function () {
    config(['app.walking_speed' => 2]);
    City::factory()->create();

    mock(DestinationPicker::class, function ($mock) {
        $mock->shouldNotReceive('pick');

        $index = 0;
        $mock->shouldReceive('pickCityCenter')
            ->andReturnUsing(function () use (&$index) {
                return new PickedDestination(farApartLocation($index++), null);
            });
    });

    $this->seed(TripSeeder::class);

    expect(Trip::count())->toBeGreaterThan(0);
});

it('should geocode every stop once seed geocoding is turned on', function () {
    config(['app.walking_speed' => 2, 'app.seed_geocoding' => true]);
    City::factory()->create();

    mock(DestinationPicker::class, function ($mock) {
        $mock->shouldNotReceive('pickCityCenter');

        $index = 0;
        $mock->shouldReceive('pick')
            ->andReturnUsing(function (?Location $origin = null) use (&$index) {
                return new PickedDestination(
                    farApartLocation($index++),
                    $origin === null ? null : 9000.0,
                );
            });
    });

    $this->seed(TripSeeder::class);

    expect((float) Trip::oldest('departure')->first()->distance)->toBe(9000.0);
});

it('should leave no gap between one trip arriving and the next departing', function () {
    config(['app.walking_speed' => 2]);
    City::factory()->create();
    pickerAlternating('pickCityCenter');

    $this->seed(TripSeeder::class);

    $trips = Trip::orderBy('id')->get();

    // skip() preserves the original keys, so the first pair here is 0 and 1.
    foreach ($trips->skip(1) as $index => $trip) {
        $previous = $trips[$index - 1];

        expect($trip->departure)->toBe($previous->arrival)
            ->and($trip->origin_location_id)->toBe($previous->destination_location_id);
    }

    expect($trips->count())->toBeGreaterThan(1);
});

it('should record every trip as randomly chosen rather than user chosen', function () {
    config(['app.walking_speed' => 2]);
    City::factory()->create();
    pickerAlternating('pickCityCenter');

    $this->seed(TripSeeder::class);

    Trip::all()->each(function (Trip $trip) {
        expect($trip->destination_from_user)->toBeFalse()
            ->and($trip->destination_is_random)->toBeTrue()
            ->and($trip->user_id)->toBeNull()
            ->and($trip->origin_location_id)->not->toBeNull()
            ->and($trip->destination_location_id)->not->toBeNull();
    });
});

it('should walk the straight line when no driving distance comes back', function () {
    config(['app.walking_speed' => 2]);
    City::factory()->create();
    pickerAlternating('pickCityCenter');

    $this->seed(TripSeeder::class);

    $first = Trip::oldest('departure')->first();
    $expected = round(Trip::calculateDistance($first->originLocation, $first->destinationLocation), 2);

    expect(round((float) $first->distance, 2))->toBe($expected);
});

it('should refuse to run before any cities are seeded', function () {
    mock(DestinationPicker::class, function ($mock) {
        $mock->shouldNotReceive('pick');
        $mock->shouldNotReceive('pickCityCenter');
    });

    expect(fn () => $this->seed(TripSeeder::class))
        ->toThrow(RuntimeException::class, 'Seed cities first');

    expect(Trip::count())->toBe(0);
});
