<?php

use App\Models\Trip;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

test('a timestamp inside a spring-forward gap survives the round trip', function () {
    // 02:30 never happens on this date in US Eastern time. Reading the app's UTC
    // output as local time, MySQL rejects the value outright rather than storing
    // it, which is what leaving the session timezone on SYSTEM does.
    $arrival = Carbon::parse('2026-03-08 02:30:00');

    $trip = Trip::factory()->create([
        'departure' => $arrival->copy()->subDay(),
        'arrival' => $arrival,
    ]);

    expect($trip->fresh()->arrivesAt()->toDateTimeString())->toBe('2026-03-08 02:30:00');
})->skip(
    fn () => DB::connection()->getDriverName() !== 'mysql',
    'Only a server-side session timezone can misread the value.',
);

test('every server-side connection is pinned to the timezone the app writes', function () {
    // Guards the config the test above cannot reach on the SQLite suite.
    expect(config('app.timezone'))->toBe('UTC')
        ->and(config('database.connections.mysql.timezone'))->toBe('+00:00')
        ->and(config('database.connections.mariadb.timezone'))->toBe('+00:00');
});
