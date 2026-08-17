<?php

use App\Models\City;
use App\Models\Location;
use App\Models\Trip;
use Illuminate\Support\Carbon;
use Livewire\Volt\Volt;

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

test('it stands him on the origin of a trip that has just departed', function () {
    $this->travelTo(Carbon::parse('2026-01-01 12:00:00'));

    Trip::factory()->create([
        'departure' => now(),
        'arrival' => now()->addDays(2),
    ]);

    $response = $this->get('/');

    $response->assertSee('margin-inline-start: 0%');
});

test('it walks him to the destination and announces his arrival on a page left open', function () {
    $this->travelTo(Carbon::parse('2026-01-01 12:00:00'));

    Trip::factory()->create([
        'departure' => now()->subHour(),
        'arrival' => now()->addHour(),
    ]);

    $page = Volt::test('home');

    $page->assertSee('margin-inline-start: 50%')
        ->assertSee('wire:poll.60s')
        ->assertDontSee('Jack has arrived');

    $this->travelTo(now()->addHours(2));

    $page->call('$refresh')
        ->assertSee('margin-inline-start: 100%')
        ->assertSee('Jack has arrived! Reload the page to see his next trip.')
        ->assertSee('0 miles remaining.')
        ->assertDontSee('wire:poll');
});

test('it shows him standing alone when he has no trip to walk', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('walking-man.png')
        ->assertDontSee('margin-inline-start');
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

test('it does not present an arrived trip as still underway', function () {
    Trip::factory()->create([
        'departure' => now()->subDays(10),
        'arrival' => now()->subDay(),
    ]);

    $response = $this->get('/');

    $response->assertOk()
        ->assertDontSee('miles remaining')
        ->assertDontSee('Arriving in');
});

test('it ignores a trip whose locations were never backfilled', function () {
    Trip::factory()->create([
        'origin_location_id' => null,
        'destination_location_id' => null,
        'departure' => now()->subDays(10),
        'arrival' => now()->addDays(2),
    ]);

    $response = $this->get('/');

    $response->assertOk()->assertDontSee('miles remaining');
});

test('it reads the arrival date in the timezone it labels', function () {
    $this->travelTo(Carbon::parse('2026-08-14 12:00:00'));

    Trip::factory()->create([
        'departure' => now()->subDay(),
        'arrival' => Carbon::parse('2026-08-15 01:00:00'),
    ]);

    $response = $this->get('/');

    // 01:00 UTC has not reached the 15th in either labelled zone yet.
    $response->assertSee('21:00 ET / 18:00 PT')
        ->assertSee('8/14/26')
        ->assertDontSee('8/15/26');
});

test('it does not show the play button or leaderboard link', function () {
    Trip::factory()->create([
        'departure' => now()->subDays(10),
        'arrival' => now()->addDays(2),
    ]);

    $response = $this->get('/');

    $response->assertDontSee('PLAY')
        ->assertDontSee('Leaderboard');
});

test('it hides lifetime stats until a trip has been completed', function () {
    Trip::factory()->create([
        'departure' => now()->subDays(10),
        'arrival' => now()->addDays(2),
    ]);

    $response = $this->get('/');

    $response->assertOk()
        ->assertDontSee('Lifetime stats')
        ->assertDontSee('trips completed')
        ->assertDontSee('miles walked');
});

test('it counts completed trips and the miles they covered', function () {
    Trip::factory()->create([
        'departure' => now()->subDays(20),
        'arrival' => now()->subDays(15),
        'distance' => 1200.50,
    ]);

    Trip::factory()->create([
        'departure' => now()->subDays(15),
        'arrival' => now()->subDays(10),
        'distance' => 800.25,
    ]);

    $response = $this->get('/');

    $response->assertSee('Lifetime stats')
        ->assertSee('2 trips completed.')
        ->assertSee('2,001 miles walked.');
});

test('it leaves the trip underway out of the lifetime stats', function () {
    Trip::factory()->create([
        'departure' => now()->subDays(20),
        'arrival' => now()->subDays(15),
        'distance' => 500,
    ]);

    Trip::factory()->create([
        'departure' => now()->subDay(),
        'arrival' => now()->addDays(3),
        'distance' => 9000,
    ]);

    $response = $this->get('/');

    $response->assertSee('1 trip completed.')
        ->assertSee('500 miles walked.');
});

test('it counts each city and state visited once', function () {
    $minneapolis = City::factory()->create(['name' => 'Minneapolis', 'state_abbreviation' => 'MN']);
    $dallas = City::factory()->create(['name' => 'Dallas', 'state_abbreviation' => 'TX']);

    $first = Location::factory()->for($minneapolis)->create();
    $second = Location::factory()->for($dallas)->create();
    $third = Location::factory()->for($minneapolis)->create();

    Trip::factory()->create([
        'origin_location_id' => $first,
        'destination_location_id' => $second,
        'departure' => now()->subDays(20),
        'arrival' => now()->subDays(15),
    ]);

    Trip::factory()->create([
        'origin_location_id' => $second,
        'destination_location_id' => $third,
        'departure' => now()->subDays(15),
        'arrival' => now()->subDays(10),
    ]);

    $response = $this->get('/');

    // Three addresses, but Minneapolis is visited twice and counted once.
    $response->assertSee('2 cities in 2 states visited.');
});

test('it measures time walking from the first departure', function () {
    $this->travelTo(Carbon::parse('2026-08-15 12:00:00'));

    Trip::factory()->create([
        'departure' => Carbon::parse('2021-02-14 12:00:00'),
        'arrival' => Carbon::parse('2021-02-20 12:00:00'),
    ]);

    $response = $this->get('/');

    // Five whole years to 2/14/26, then 182 days on top of that.
    $response->assertSee('5 years, 182 days walking.');
});

test('it drops the years from the time walking during the first one', function () {
    $this->travelTo(Carbon::parse('2026-08-15 12:00:00'));

    Trip::factory()->create([
        'departure' => Carbon::parse('2026-08-03 12:00:00'),
        'arrival' => Carbon::parse('2026-08-04 12:00:00'),
    ]);

    $response = $this->get('/');

    $response->assertSee('12 days walking.')
        ->assertDontSee('0 years');
});

test('it uses singular copy throughout when every count is one', function () {
    $this->travelTo(Carbon::parse('2026-08-15 12:00:00'));

    $city = City::factory()->create(['state_abbreviation' => 'MN']);

    Trip::factory()->create([
        'origin_location_id' => Location::factory()->for($city),
        'destination_location_id' => Location::factory()->for($city),
        'departure' => Carbon::parse('2025-08-14 12:00:00'),
        'arrival' => Carbon::parse('2025-08-15 12:00:00'),
        'distance' => 12,
    ]);

    $response = $this->get('/');

    $response->assertSee('1 trip completed.')
        ->assertSee('1 year, 1 day walking.')
        ->assertSee('1 city in 1 state visited.');
});
