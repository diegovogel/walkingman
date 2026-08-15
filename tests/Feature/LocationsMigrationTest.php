<?php

use App\Models\Location;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

test('the locations migration rolls back and re-applies without losing city associations', function () {
    $origin = Location::factory()->create();
    $destination = Location::factory()->create();

    $trip = Trip::factory()->create([
        'origin_location_id' => $origin->id,
        'destination_location_id' => $destination->id,
    ]);

    $this->artisan('migrate:rollback', ['--step' => 2])->assertSuccessful();

    $rolledBack = DB::table('trips')->where('id', $trip->id)->first();

    expect((int) $rolledBack->origin_city_id)->toBe($origin->city_id)
        ->and((int) $rolledBack->destination_city_id)->toBe($destination->city_id);

    $this->artisan('migrate')->assertSuccessful();

    $reapplied = DB::table('trips')->where('id', $trip->id)->first();

    expect((int) DB::table('locations')->where('id', $reapplied->origin_location_id)->value('city_id'))->toBe($origin->city_id)
        ->and((int) DB::table('locations')->where('id', $reapplied->destination_location_id)->value('city_id'))->toBe($destination->city_id);
});
