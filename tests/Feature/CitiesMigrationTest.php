<?php

use App\Models\City;
use App\Models\Location;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('the population migration backfills existing cities from the seed data', function () {
    $this->artisan('migrate:rollback', ['--path' => [
        'database/migrations/2026_08_17_161008_remove_non_contiguous_cities_from_cities_table.php',
        'database/migrations/2026_08_17_152309_widen_distance_precision_on_trips_table.php',
        'database/migrations/2026_08_17_152155_add_population_to_cities_table.php',
    ]])->assertSuccessful();

    DB::table('cities')->insert([
        [
            'name' => 'New York',
            'state_abbreviation' => 'NY',
            'state_name' => 'New York',
            'latitude' => 40.6943,
            'longitude' => -73.9249,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => 'Faketown',
            'state_abbreviation' => 'ZZ',
            'state_name' => 'Nowhere',
            'latitude' => 0,
            'longitude' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $this->artisan('migrate')->assertSuccessful();

    expect(City::where('name', 'New York')->value('population'))->toBe(18972871)
        ->and(City::where('name', 'Faketown')->value('population'))->toBeNull();
});

test('the unique-city migration folds duplicate rows together and blocks new duplicates', function () {
    $this->artisan('migrate:rollback', ['--path' => [
        'database/migrations/2026_08_17_163751_add_unique_city_constraint_to_cities_table.php',
    ]])->assertSuccessful();

    $kept = City::factory()->create(['name' => 'Boston', 'state_abbreviation' => 'MA']);
    $redundant = City::factory()->create(['name' => 'Boston', 'state_abbreviation' => 'MA']);
    $location = Location::factory()->for($redundant)->create();

    $this->artisan('migrate')->assertSuccessful();

    expect(City::where('name', 'Boston')->count())->toBe(1)
        ->and($location->fresh()->city_id)->toBe($kept->id)
        ->and(fn () => City::factory()->create(['name' => 'Boston', 'state_abbreviation' => 'MA']))
        ->toThrow(QueryException::class);
});

test('the non-contiguous cities migration removes previously seeded rows and their locations', function () {
    $this->artisan('migrate:rollback', ['--path' => [
        'database/migrations/2026_08_17_161008_remove_non_contiguous_cities_from_cities_table.php',
    ]])->assertSuccessful();

    $honolulu = City::factory()->create(['name' => 'Honolulu', 'state_abbreviation' => 'HI']);
    $boston = City::factory()->create(['name' => 'Boston', 'state_abbreviation' => 'MA']);
    $honoluluLocation = Location::factory()->for($honolulu)->create();

    $this->artisan('migrate')->assertSuccessful();

    expect(City::whereKey($honolulu->id)->exists())->toBeFalse()
        ->and(Location::whereKey($honoluluLocation->id)->exists())->toBeFalse()
        ->and(City::whereKey($boston->id)->exists())->toBeTrue();
});
