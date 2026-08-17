<?php

use App\Models\Trip;
use Illuminate\Support\Carbon;

test('a trip that just departed has made no progress', function () {
    $this->travelTo(Carbon::parse('2026-01-01 12:00:00'));

    $trip = Trip::factory()->create([
        'departure' => now(),
        'arrival' => now()->addDay(),
    ]);

    expect($trip->progress())->toBe(0.0);
});

test('it measures progress by the share of the trip already spent', function () {
    $this->travelTo(Carbon::parse('2026-01-01 12:00:00'));

    $halfway = Trip::factory()->create([
        'departure' => now()->subHours(12),
        'arrival' => now()->addHours(12),
    ]);

    $threeQuarters = Trip::factory()->create([
        'departure' => now()->subHours(18),
        'arrival' => now()->addHours(6),
    ]);

    expect($halfway->progress())->toBe(0.5)
        ->and($threeQuarters->progress())->toBe(0.75);
});

test('progress stops at the destination however long ago he arrived', function () {
    $this->travelTo(Carbon::parse('2026-01-01 12:00:00'));

    $trip = Trip::factory()->create([
        'departure' => now()->subDays(10),
        'arrival' => now()->subDay(),
    ]);

    expect($trip->progress())->toBe(1.0);
});

test('a trip with no duration counts as walked', function () {
    $this->travelTo(Carbon::parse('2026-01-01 12:00:00'));

    $trip = Trip::factory()->create([
        'departure' => now(),
        'arrival' => now(),
    ]);

    expect($trip->progress())->toBe(1.0);
});

test('it knows whether he has arrived yet', function () {
    $this->travelTo(Carbon::parse('2026-01-01 12:00:00'));

    $underway = Trip::factory()->create([
        'departure' => now()->subDay(),
        'arrival' => now()->addDay(),
    ]);

    $arrived = Trip::factory()->create([
        'departure' => now()->subDays(10),
        'arrival' => now()->subMinute(),
    ]);

    expect($underway->hasArrived())->toBeFalse()
        ->and($arrived->hasArrived())->toBeTrue();
});
