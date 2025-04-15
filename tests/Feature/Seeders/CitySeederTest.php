<?php

use App\Models\City;
use Database\Seeders\CitySeeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seeder = Mockery::mock(CitySeeder::class)->makePartial();
});

it('should add the cities from the JSON file to the database', function () {
    $this->seeder->shouldAllowMockingProtectedMethods()
        ->shouldReceive('getFilePath')
        ->once()
        ->andReturn(__DIR__.'/../../Fixtures/cities.json');

    $this->seeder->run();

    expect(City::count())->toBe(3)
        ->and(City::first()->name)->toBe('New York');
});

it('should log an error if the JSON is invalid', function () {
    $this->seeder->shouldAllowMockingProtectedMethods()
        ->shouldReceive('getFilePath')
        ->once()
        ->andReturn(__DIR__.'/../../Fixtures/cities-invalid.json');

    Log::shouldReceive('error')
        ->once()
        ->withArgs(fn ($message) => Str::contains($message, 'json_decode error'));

    $this->seeder->run();
});

it('should log an error if the JSON file is not found', function () {
    $this->seeder->shouldAllowMockingProtectedMethods()
        ->shouldReceive('getFilePath')
        ->once()
        ->andReturn(__DIR__.'/../../Fixtures/non-existent.json');

    Log::shouldReceive('error')
        ->once()
        ->withArgs(fn ($message) => Str::contains($message, 'file does not exist'));

    $this->seeder->run();
});
