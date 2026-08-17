<?php

use App\Models\City;
use App\Models\Trip;
use Database\Seeders\TripSeeder;

it('should walk the chain from two years back up to the trip underway', function () {
    config(['app.walking_speed' => 2]);
    City::factory()->count(20)->create();

    (new TripSeeder)->run();

    expect(Trip::underway()->count())->toBe(1)
        ->and(Trip::completed()->count())->toBeGreaterThan(0)
        ->and(Trip::oldest('departure')->first()->departedAt()->diffInYears(now()))
        ->toBeGreaterThanOrEqual(2);
});

it('should leave no gap between one trip arriving and the next departing', function () {
    config(['app.walking_speed' => 2]);
    City::factory()->count(20)->create();

    (new TripSeeder)->run();

    $trips = Trip::orderBy('id')->get();

    // skip() preserves the original keys, so the first pair here is 0 and 1.
    foreach ($trips->skip(1) as $index => $trip) {
        $previous = $trips[$index - 1];

        expect($trip->departure)->toBe($previous->arrival)
            ->and($trip->origin_location_id)->toBe($previous->destination_location_id);
    }
});

it('should refuse to run before any cities are seeded', function () {
    expect(fn () => (new TripSeeder)->run())
        ->toThrow(RuntimeException::class, 'Seed cities first');

    expect(Trip::count())->toBe(0);
});
