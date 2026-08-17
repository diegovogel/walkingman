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
function geocodioReverseResponse(array $resultOverrides = [], array $componentOverrides = []): array
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

function geocodioDistanceResponse(float $miles = 12.5): array
{
    return [
        'origin' => ['location' => [44.97, -93.26]],
        'destinations' => [
            ['location' => [44.9585, -93.2735], 'distance_miles' => $miles, 'duration_seconds' => 15000],
        ],
    ];
}

function createMinneapolis(): City
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
    $city = createMinneapolis();

    mock(Geocodio::class, function ($mock) {
        $mock->shouldReceive('reverse')->once()->andReturn(geocodioReverseResponse());
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
    $city = createMinneapolis();
    $dallas = City::factory()->create(['name' => 'Dallas', 'state_abbreviation' => 'TX']);
    $origin = Location::factory()->for($dallas)->create();

    mock(Geocodio::class, function ($mock) {
        $mock->shouldReceive('reverse')->once()->andReturn(geocodioReverseResponse());
        $mock->shouldReceive('distance')->once()->andReturn(geocodioDistanceResponse(942.3));
    });

    $picked = app(DestinationPicker::class)->pick($origin);

    expect($picked->drivingDistanceMiles)->toBe(942.3)
        ->and($picked->location->city_id)->toBe($city->id);
});

it('never picks a destination in the origin city', function () {
    $city = createMinneapolis();
    $origin = Location::factory()->for($city)->create();

    City::factory()->create([
        'name' => 'Saint Paul',
        'state_abbreviation' => 'MN',
        'latitude' => 44.9489,
        'longitude' => -93.1041,
        'population' => 300000,
    ]);

    mock(Geocodio::class, function ($mock) {
        $mock->shouldReceive('reverse')->andReturn(geocodioReverseResponse([], ['city' => 'Saint Paul']));
        $mock->shouldReceive('distance')->andReturn(geocodioDistanceResponse());
    });

    $picked = app(DestinationPicker::class)->pick($origin);

    expect($picked->location->city_id)->not->toBe($city->id);
});

it('rejects unusable results and accepts a later attempt', function (array $badResponse) {
    createMinneapolis();

    mock(Geocodio::class, function ($mock) use ($badResponse) {
        $mock->shouldReceive('reverse')->twice()->andReturn($badResponse, geocodioReverseResponse());
    });

    $picked = app(DestinationPicker::class)->pick();

    expect($picked->location->street_address)->toBe('2400 Third Ave S');
})->with([
    'nearest_street (estimated house number)' => [geocodioReverseResponse(['accuracy_type' => 'nearest_street'])],
    'street_center' => [geocodioReverseResponse(['accuracy_type' => 'street_center'])],
    'place (city centroid)' => [geocodioReverseResponse(['accuracy_type' => 'place'])],
    'nearest_rooftop_match below accuracy threshold' => [geocodioReverseResponse(['accuracy_type' => 'nearest_rooftop_match', 'accuracy' => 0.7])],
    'missing house number' => [geocodioReverseResponse([], ['number' => ''])],
    'across the border in Canada' => [geocodioReverseResponse([], ['state_province' => 'ON', 'country' => 'CA'])],
    'missing city component' => [geocodioReverseResponse([], ['city' => ''])],
    'empty result set' => [['results' => []]],
]);

it('persists the municipality the address actually sits in, discovering it on first sight', function () {
    $minneapolis = createMinneapolis();

    mock(Geocodio::class, function ($mock) {
        $mock->shouldReceive('reverse')->once()->andReturn(
            geocodioReverseResponse([], ['city' => 'Richfield', 'postal_code' => '55423']),
        );
    });

    $picked = app(DestinationPicker::class)->pick();

    $richfield = City::where('name', 'Richfield')->first();

    expect($richfield)->not->toBeNull()
        ->and($richfield->state_abbreviation)->toBe('MN')
        ->and($richfield->state_name)->toBe($minneapolis->state_name)
        ->and($richfield->latitude)->toBe(44.9585)
        ->and($richfield->longitude)->toBe(-93.2735)
        ->and($picked->location->city_id)->toBe($richfield->id)
        ->and($picked->location->full_address)->toBe('2400 Third Ave S, Richfield, MN 55423');
});

it('reuses the existing city row when the address matches one', function () {
    createMinneapolis();
    $saintPaul = City::factory()->create([
        'name' => 'Saint Paul',
        'state_abbreviation' => 'MN',
        'population' => 300000,
    ]);

    mock(Geocodio::class, function ($mock) {
        $mock->shouldReceive('reverse')->once()->andReturn(
            geocodioReverseResponse([], ['city' => 'Saint Paul']),
        );
    });

    $picked = app(DestinationPicker::class)->pick();

    expect($picked->location->city_id)->toBe($saintPaul->id)
        ->and(City::count())->toBe(2);
});

it('rejects an address that resolves back into the origin city', function () {
    $minneapolis = createMinneapolis();
    $origin = Location::factory()->for($minneapolis)->create();

    City::factory()->create([
        'name' => 'Saint Paul',
        'state_abbreviation' => 'MN',
        'population' => 300000,
    ]);

    mock(Geocodio::class, function ($mock) {
        $mock->shouldReceive('reverse')->twice()->andReturn(
            geocodioReverseResponse(),
            geocodioReverseResponse([], ['city' => 'Saint Paul']),
        );
        $mock->shouldReceive('distance')->once()->andReturn(geocodioDistanceResponse(11.0));
    });

    $picked = app(DestinationPicker::class)->pick($origin);

    expect($picked->location->city->name)->toBe('Saint Paul');
});

it('rejects an address in a state the app has never seen', function () {
    createMinneapolis();

    mock(Geocodio::class, function ($mock) {
        $mock->shouldReceive('reverse')->twice()->andReturn(
            geocodioReverseResponse([], ['state_province' => 'XQ']),
            geocodioReverseResponse(),
        );
    });

    $picked = app(DestinationPicker::class)->pick();

    expect($picked->location->full_address)->toBe('2400 Third Ave S, Minneapolis, MN 55404')
        ->and(City::where('state_abbreviation', 'XQ')->exists())->toBeFalse();
});

it('rejects a candidate with no driving route and accepts a later attempt', function () {
    createMinneapolis();
    $dallas = City::factory()->create(['name' => 'Dallas', 'state_abbreviation' => 'TX']);
    $origin = Location::factory()->for($dallas)->create();

    mock(Geocodio::class, function ($mock) {
        $mock->shouldReceive('reverse')->twice()->andReturn(geocodioReverseResponse());
        $mock->shouldReceive('distance')->twice()->andReturn(['destinations' => []], geocodioDistanceResponse(950.0));
    });

    $picked = app(DestinationPicker::class)->pick($origin);

    expect($picked->drivingDistanceMiles)->toBe(950.0);
});

it('treats a Geocodio exception as a failed attempt rather than propagating', function () {
    createMinneapolis();

    mock(Geocodio::class, function ($mock) {
        $mock->shouldReceive('reverse')->twice()->andReturnUsing(
            fn () => throw GeocodioException::requestError('server error'),
            fn () => geocodioReverseResponse(),
        );
    });

    $picked = app(DestinationPicker::class)->pick();

    expect($picked->location->street_address)->toBe('2400 Third Ave S');
});

it('falls back to the city center after exhausting all attempts', function () {
    $city = createMinneapolis();

    mock(Geocodio::class, function ($mock) {
        $mock->shouldReceive('reverse')->times(5)->andReturn(geocodioReverseResponse(['accuracy_type' => 'nearest_street']));
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

it('samples points within the population-scaled radius, corrected for longitude shrink', function (float $latitude, float $longitude, int $population) {
    $city = City::factory()->create([
        'latitude' => $latitude,
        'longitude' => $longitude,
        'population' => $population,
    ]);

    $queries = [];

    mock(Geocodio::class, function ($mock) use (&$queries) {
        $mock->shouldReceive('reverse')->andReturnUsing(function ($query) use (&$queries) {
            $queries[] = $query;

            throw GeocodioException::requestError('capture only');
        });
    });

    for ($i = 0; $i < 10; $i++) {
        app(DestinationPicker::class)->pick();
    }

    $radius = min(max(0.011 * sqrt($population), 1.5), 10.0);

    expect($queries)->toHaveCount(50);

    foreach ($queries as $query) {
        [$lat, $lng] = array_map(floatval(...), explode(',', $query));

        $milesNorth = ($lat - $city->latitude) * 69;
        $milesEast = ($lng - $city->longitude) * 69 * cos(deg2rad($city->latitude));
        $distance = sqrt($milesNorth ** 2 + $milesEast ** 2);

        expect($distance)->toBeLessThanOrEqual($radius + 0.01);
    }
})->with([
    // Seattle sits far enough north that uncorrected longitude offsets would
    // stretch the sampled disc by ~30%.
    'Seattle: large city, high latitude' => [47.6211, -122.3244, 500000],
    'Leesburg: small city, smaller radius' => [39.1057, -77.5544, 48000],
]);
