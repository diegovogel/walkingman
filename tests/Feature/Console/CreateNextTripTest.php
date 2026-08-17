<?php

use App\Models\Location;
use App\Models\Trip;
use App\Services\DestinationPicker;
use App\Services\PickedDestination;
use Illuminate\Support\Carbon;

use function Pest\Laravel\mock;

it('does nothing while a trip is underway', function () {
    Trip::factory()->create([
        'departure' => Carbon::now()->subDay(),
        'arrival' => Carbon::now()->addDay(),
    ]);

    mock(DestinationPicker::class, function ($mock) {
        $mock->shouldNotReceive('pick');
    });

    $this->artisan('trip:next')
        ->expectsOutputToContain('already underway')
        ->assertSuccessful();

    expect(Trip::count())->toBe(1);
});

it('creates the next trip from the previous destination with the driving distance', function () {
    $previousTrip = Trip::factory()->create([
        'departure' => Carbon::now()->subDays(20),
        'arrival' => Carbon::now()->subDay(),
    ]);
    $origin = $previousTrip->destinationLocation;

    $destination = Location::factory()->create();

    mock(DestinationPicker::class, function ($mock) use ($origin, $destination) {
        $mock->shouldReceive('pick')
            ->once()
            ->withArgs(fn (?Location $pickOrigin) => $pickOrigin?->id === $origin->id)
            ->andReturn(new PickedDestination($destination, 1200.5));
    });

    $this->artisan('trip:next')->assertSuccessful();

    $trip = Trip::latest('id')->first();

    expect($trip->origin_location_id)->toBe($origin->id)
        ->and($trip->destination_location_id)->toBe($destination->id)
        ->and((float) $trip->distance)->toBe(1200.5)
        ->and($trip->destination_from_user)->toBeFalse()
        ->and($trip->destination_is_random)->toBeTrue()
        ->and($trip->user_id)->toBeNull();
});

it('departs now and derives the arrival from the driving distance', function () {
    Carbon::setTestNow('2026-08-17 12:00:00');
    config(['app.walking_speed' => 3]);

    Trip::factory()->create([
        'departure' => Carbon::now()->subDays(20),
        'arrival' => Carbon::now()->subDays(3),
    ]);

    $destination = Location::factory()->create();

    mock(DestinationPicker::class, function ($mock) use ($destination) {
        $mock->shouldReceive('pick')->once()->andReturn(new PickedDestination($destination, 300.0));
    });

    $this->artisan('trip:next')->assertSuccessful();

    $trip = Trip::latest('id')->first();

    expect($trip->departedAt()->toDateTimeString())->toBe('2026-08-17 12:00:00')
        ->and($trip->arrivesAt()->toDateTimeString())->toBe('2026-08-21 16:00:00');
});

it('falls back to the straight-line distance when no driving distance is available', function () {
    $previousTrip = Trip::factory()->create([
        'departure' => Carbon::now()->subDays(20),
        'arrival' => Carbon::now()->subDay(),
    ]);
    $origin = $previousTrip->destinationLocation;

    $destination = Location::factory()->create();

    mock(DestinationPicker::class, function ($mock) use ($destination) {
        $mock->shouldReceive('pick')->once()->andReturn(new PickedDestination($destination, null));
    });

    $this->artisan('trip:next')->assertSuccessful();

    $trip = Trip::latest('id')->first();
    $expected = round(Trip::calculateDistance($origin, $destination), 2);

    expect(round((float) $trip->distance, 2))->toBe($expected);
});

it('picks an origin as well when there are no trips yet', function () {
    $origin = Location::factory()->create();
    $destination = Location::factory()->create();

    mock(DestinationPicker::class, function ($mock) use ($origin, $destination) {
        $mock->shouldReceive('pick')
            ->once()
            ->withNoArgs()
            ->andReturn(new PickedDestination($origin, null));
        $mock->shouldReceive('pick')
            ->once()
            ->withArgs(fn (?Location $pickOrigin) => $pickOrigin?->id === $origin->id)
            ->andReturn(new PickedDestination($destination, 800.0));
    });

    $this->artisan('trip:next')->assertSuccessful();

    $trip = Trip::sole();

    expect($trip->origin_location_id)->toBe($origin->id)
        ->and($trip->destination_location_id)->toBe($destination->id);
});

it('replaces a mid-window trip whose locations were removed', function () {
    $ghost = Trip::factory()->create([
        'departure' => Carbon::now()->subDay(),
        'arrival' => Carbon::now()->addDay(),
    ]);
    $ghost->update(['origin_location_id' => null, 'destination_location_id' => null]);

    $origin = Location::factory()->create();
    $destination = Location::factory()->create();

    mock(DestinationPicker::class, function ($mock) use ($origin, $destination) {
        $mock->shouldReceive('pick')->once()->withNoArgs()->andReturn(new PickedDestination($origin, null));
        $mock->shouldReceive('pick')->once()->with($origin)->andReturn(new PickedDestination($destination, 700.0));
    });

    $this->artisan('trip:next')->assertSuccessful();

    expect(Trip::count())->toBe(2)
        ->and(Trip::latest('id')->first()->destination_location_id)->toBe($destination->id);
});

it('starts a new trip when the previous trip has no destination location', function () {
    $trip = Trip::factory()->create([
        'departure' => Carbon::now()->subDays(20),
        'arrival' => Carbon::now()->subDay(),
    ]);
    $trip->update(['destination_location_id' => null]);

    $origin = Location::factory()->create();
    $destination = Location::factory()->create();

    mock(DestinationPicker::class, function ($mock) use ($origin, $destination) {
        $mock->shouldReceive('pick')->once()->withNoArgs()->andReturn(new PickedDestination($origin, null));
        $mock->shouldReceive('pick')->once()->with($origin)->andReturn(new PickedDestination($destination, 500.0));
    });

    $this->artisan('trip:next')->assertSuccessful();

    expect(Trip::latest('id')->first()->origin_location_id)->toBe($origin->id);
});
