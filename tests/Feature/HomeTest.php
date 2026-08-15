<?php

use App\Models\City;
use App\Models\Location;
use App\Models\Trip;
use Illuminate\Support\Carbon;

test('the home page renders for guests', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Walking Man')
        ->assertSee('Where will he go next?');
});

test('the home page renders before any trips exist', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('What is this?')
        ->assertDontSee('miles remaining');
});

test('it shows the full street address of the origin and destination', function () {
    $origin = Location::factory()
        ->for(City::factory()->create(['name' => 'Minneapolis', 'state_abbreviation' => 'MN']))
        ->create(['street_address' => '2400 Third Ave S', 'postal_code' => '55404']);

    $destination = Location::factory()
        ->for(City::factory()->create(['name' => 'Dallas', 'state_abbreviation' => 'TX']))
        ->create(['street_address' => '1717 N Harwood St', 'postal_code' => '75201']);

    Trip::factory()->create([
        'origin_location_id' => $origin,
        'destination_location_id' => $destination,
        'departure' => now()->subDays(10),
        'arrival' => now()->addDays(2),
    ]);

    $response = $this->get('/');

    $response->assertSee('2400 Third Ave S, Minneapolis, MN 55404')
        ->assertSee('1717 N Harwood St, Dallas, TX 75201');
});

test('it shows how far the walking man has left to go', function () {
    config(['app.walking_speed' => 3]);
    $this->travelTo(Carbon::parse('2026-01-01 12:00:00'));

    Trip::factory()->create([
        'departure' => now()->subDays(10),
        'arrival' => now()->addDays(2)->addHours(3)->addMinutes(4),
    ]);

    $response = $this->get('/');

    // 51h 4m left at 3 mph.
    $response->assertSee('153 miles remaining.')
        ->assertSee('Arriving in 2 d, 3 h, 4 m.');
});

test('it shows the trip that is currently underway', function () {
    $underway = Trip::factory()->create([
        'departure' => now()->subDays(10),
        'arrival' => now()->addDays(2),
    ]);

    $notYetDeparted = Trip::factory()->create([
        'departure' => now()->addDays(2),
        'arrival' => now()->addDays(9),
    ]);

    $response = $this->get('/');

    $response->assertSee($underway->destinationLocation->full_address)
        ->assertDontSee($notYetDeparted->destinationLocation->full_address);
});

test('it does not show the play button, leaderboard link, or lifetime stats', function () {
    Trip::factory()->create([
        'departure' => now()->subDays(10),
        'arrival' => now()->addDays(2),
    ]);

    $response = $this->get('/');

    // The body copy says "play" in lowercase, so match the button's label exactly.
    $response->assertDontSee('PLAY')
        ->assertDontSee('Leaderboard')
        ->assertDontSee('trips completed')
        ->assertDontSee('miles walked');
});
