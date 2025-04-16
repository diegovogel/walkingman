<?php

use App\Models\Trip;
use Database\Seeders\CitySeeder;

beforeEach(function () {
    $citySeeder = new CitySeeder;
    $citySeeder->run();
});

it('should create the specified number of trips', function () {
    Trip::factory()->count(10)->create();
    expect(Trip::count())->toBe(10);
});

it('should create trips that originate where the previous trip ended', function () {
    for ($i = 0; $i < 3; $i++) {
        Trip::factory()->create();
    }

    $destination1 = Trip::first()->destinationCity;
    $destination2 = Trip::skip(1)->first()->destinationCity;

    $origin2 = Trip::skip(1)->first()->originCity;
    $origin3 = Trip::skip(2)->first()->originCity;

    expect($destination1->id)->toBe($origin2->id)
        ->and($destination2->id)->toBe($origin3->id);
});

it('should create trips with a departure time equal to the arrival of the previous trip', function () {
    for ($i = 0; $i < 3; $i++) {
        Trip::factory()->create();
    }

    $arrival1 = Trip::first()->arrival;
    $arrival2 = Trip::skip(1)->first()->arrival;

    $departure2 = Trip::skip(1)->first()->departure;
    $departure3 = Trip::skip(2)->first()->departure;

    expect($arrival1)->toBe($departure2)
        ->and($arrival2)->toBe($departure3);
});

it('should assign a user to all trips that came from a user', function () {
    for ($i = 0; $i < 30; $i++) {
        Trip::factory()->create();
    }

    foreach (Trip::all() as $trip) {
        $cameFromUser = $trip->destination_from_user;

        if ($cameFromUser) {
            expect($trip->user_id)->not()->toBeNull();
        } else {
            expect($trip->user_id)->toBeNull();
        }
    }
});
