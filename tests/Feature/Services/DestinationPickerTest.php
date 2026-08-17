<?php

use App\Models\City;
use App\Models\Location;
use App\Services\DestinationPicker;
use Geocodio\Exceptions\GeocodioException;
use Geocodio\Geocodio;

use function Pest\Laravel\mock;

/**
 * A valid Geocodio v2 reverse geocode response, overridable per test.
 */
function reverseResponse(array $resultOverrides = [], array $componentOverrides = []): array
{
    $result = array_merge([
        'address_components' => array_merge([
            'number' => '2400',
            'formatted_street' => 'Third Ave S',
            'city' => 'Minneapolis',
            'state_province' => 'MN',
            'postal_code' => '55404',
            'country' => 'US',
        ], $componentOverrides),
        'formatted_address' => '2400 Third Ave S, Minneapolis, MN 55404',
        'location' => ['lat' => 44.9585, 'lng' => -93.2735],
        'accuracy' => 1,
        'accuracy_type' => 'rooftop',
    ], $resultOverrides);

    return ['results' => [$result]];
}

function distanceResponse(float $miles = 12.5): array
{
    return [
        'origin' => ['location' => [44.97, -93.26]],
        'destinations' => [
            ['location' => [44.9585, -93.2735], 'distance_miles' => $miles, 'duration_seconds' => 15000],
        ],
    ];
}

function minneapolis(): City
{
    return City::factory()->create([
        'name' => 'Minneapolis',
        'state_abbreviation' => 'MN',
        'latitude' => 44.9635,
        'longitude' => -93.2678,
        'population' => 400000,
    ]);
}

it('creates a location from a deliverable reverse geocode result', function () {
    $city = minneapolis();

    mock(Geocodio::class, function ($mock) {
        $mock->shouldReceive('reverse')->once()->andReturn(reverseResponse());
    });

    $picked = app(DestinationPicker::class)->pick();

    expect($picked->location->city_id)->toBe($city->id)
        ->and($picked->location->street_address)->toBe('2400 Third Ave S')
        ->and($picked->location->postal_code)->toBe('55404')
        ->and($picked->location->latitude)->toBe(44.9585)
        ->and($picked->location->longitude)->toBe(-93.2735)
        ->and($picked->drivingDistanceMiles)->toBeNull();
});

it('validates the driving route and returns the distance when picking from an origin', function () {
    $city = minneapolis();
    $dallas = City::factory()->create(['name' => 'Dallas', 'state_abbreviation' => 'TX']);
    $origin = Location::factory()->for($dallas)->create();

    mock(Geocodio::class, function ($mock) {
        $mock->shouldReceive('reverse')->once()->andReturn(reverseResponse());
        $mock->shouldReceive('distance')->once()->andReturn(distanceResponse(942.3));
    });

    $picked = app(DestinationPicker::class)->pick($origin);

    expect($picked->drivingDistanceMiles)->toBe(942.3)
        ->and($picked->location->city_id)->toBe($city->id);
});

it('never picks a destination in the origin city', function () {
    $city = minneapolis();
    $origin = Location::factory()->for($city)->create();

    City::factory()->create([
        'name' => 'Saint Paul',
        'state_abbreviation' => 'MN',
        'latitude' => 44.9489,
        'longitude' => -93.1041,
        'population' => 300000,
    ]);

    mock(Geocodio::class, function ($mock) {
        $mock->shouldReceive('reverse')->andReturn(reverseResponse());
        $mock->shouldReceive('distance')->andReturn(distanceResponse());
    });

    $picked = app(DestinationPicker::class)->pick($origin);

    expect($picked->location->city_id)->not->toBe($city->id);
});

it('rejects unusable results and accepts a later attempt', function (array $badResponse) {
    minneapolis();

    mock(Geocodio::class, function ($mock) use ($badResponse) {
        $mock->shouldReceive('reverse')->twice()->andReturn($badResponse, reverseResponse());
    });

    $picked = app(DestinationPicker::class)->pick();

    expect($picked->location->street_address)->toBe('2400 Third Ave S');
})->with([
    'nearest_street (estimated house number)' => [reverseResponse(['accuracy_type' => 'nearest_street'])],
    'street_center' => [reverseResponse(['accuracy_type' => 'street_center'])],
    'place (city centroid)' => [reverseResponse(['accuracy_type' => 'place'])],
    'nearest_rooftop_match below accuracy threshold' => [reverseResponse(['accuracy_type' => 'nearest_rooftop_match', 'accuracy' => 0.7])],
    'missing house number' => [reverseResponse([], ['number' => ''])],
    'across the state line' => [reverseResponse([], ['state_province' => 'WI'])],
    'across the border in Canada' => [reverseResponse([], ['state_province' => 'ON', 'country' => 'CA'])],
    'empty result set' => [['results' => []]],
]);

it('rejects a candidate with no driving route and accepts a later attempt', function () {
    minneapolis();
    $dallas = City::factory()->create(['name' => 'Dallas', 'state_abbreviation' => 'TX']);
    $origin = Location::factory()->for($dallas)->create();

    mock(Geocodio::class, function ($mock) {
        $mock->shouldReceive('reverse')->twice()->andReturn(reverseResponse());
        $mock->shouldReceive('distance')->twice()->andReturn(['destinations' => []], distanceResponse(950.0));
    });

    $picked = app(DestinationPicker::class)->pick($origin);

    expect($picked->drivingDistanceMiles)->toBe(950.0);
});

it('treats a Geocodio exception as a failed attempt rather than propagating', function () {
    minneapolis();

    mock(Geocodio::class, function ($mock) {
        $mock->shouldReceive('reverse')->twice()->andReturnUsing(
            fn () => throw GeocodioException::requestError('server error'),
            fn () => reverseResponse(),
        );
    });

    $picked = app(DestinationPicker::class)->pick();

    expect($picked->location->street_address)->toBe('2400 Third Ave S');
});

it('falls back to the city center after exhausting all attempts', function () {
    $city = minneapolis();

    mock(Geocodio::class, function ($mock) {
        $mock->shouldReceive('reverse')->times(5)->andReturn(reverseResponse(['accuracy_type' => 'nearest_street']));
    });

    $picked = app(DestinationPicker::class)->pick();

    expect($picked->location->street_address)->toBeNull()
        ->and($picked->location->latitude)->toBe($city->latitude)
        ->and($picked->location->longitude)->toBe($city->longitude)
        ->and($picked->location->city_id)->toBe($city->id)
        ->and($picked->location->full_address)->toBe('Minneapolis, MN');
});

it('throws when no cities have been seeded', function () {
    mock(Geocodio::class);

    app(DestinationPicker::class)->pick();
})->throws(RuntimeException::class, 'no cities have been seeded');

it('samples points within the city radius, corrected for longitude shrink', function () {
    // Seattle sits far enough north that uncorrected longitude offsets would
    // stretch the sampled disc by ~30%.
    $city = City::factory()->create([
        'name' => 'Seattle',
        'state_abbreviation' => 'WA',
        'latitude' => 47.6211,
        'longitude' => -122.3244,
        'population' => 500000,
    ]);

    $queries = [];

    mock(Geocodio::class, function ($mock) use (&$queries) {
        $mock->shouldReceive('reverse')->andReturnUsing(function ($query) use (&$queries) {
            $queries[] = $query;

            throw GeocodioException::requestError('capture only');
        });
    });

    for ($i = 0; $i < 20; $i++) {
        app(DestinationPicker::class)->pick();
    }

    // radius = clamp(0.011 * sqrt(500000), 1.5, 10) = 7.78 miles
    $radius = 0.011 * sqrt(500000);

    expect($queries)->toHaveCount(100);

    foreach ($queries as $query) {
        [$lat, $lng] = array_map(floatval(...), explode(',', $query));

        $milesNorth = ($lat - $city->latitude) * 69;
        $milesEast = ($lng - $city->longitude) * 69 * cos(deg2rad($city->latitude));
        $distance = sqrt($milesNorth ** 2 + $milesEast ** 2);

        expect($distance)->toBeLessThanOrEqual($radius + 0.01);
    }
});

it('uses a smaller radius for a smaller city', function () {
    $city = City::factory()->create([
        'name' => 'Leesburg',
        'state_abbreviation' => 'VA',
        'latitude' => 39.1057,
        'longitude' => -77.5544,
        'population' => 48000,
    ]);

    $queries = [];

    mock(Geocodio::class, function ($mock) use (&$queries) {
        $mock->shouldReceive('reverse')->andReturnUsing(function ($query) use (&$queries) {
            $queries[] = $query;

            throw GeocodioException::requestError('capture only');
        });
    });

    for ($i = 0; $i < 20; $i++) {
        app(DestinationPicker::class)->pick();
    }

    // radius = clamp(0.011 * sqrt(48000), 1.5, 10) = 2.41 miles
    $radius = 0.011 * sqrt(48000);

    foreach ($queries as $query) {
        [$lat, $lng] = array_map(floatval(...), explode(',', $query));

        $milesNorth = ($lat - $city->latitude) * 69;
        $milesEast = ($lng - $city->longitude) * 69 * cos(deg2rad($city->latitude));

        expect(sqrt($milesNorth ** 2 + $milesEast ** 2))->toBeLessThanOrEqual($radius + 0.01);
    }
});
