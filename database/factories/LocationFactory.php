<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'city_id' => City::factory(),
            'street_address' => $this->faker->streetAddress(),
            'postal_code' => $this->faker->postcode(),
            'latitude' => fn (array $attributes): float => $this->near($attributes['city_id'], 'latitude'),
            'longitude' => fn (array $attributes): float => $this->near($attributes['city_id'], 'longitude'),
        ];
    }

    /**
     * Puts the address within a few miles of its city, so that generated trips
     * cover realistic distances rather than spanning the globe.
     */
    private function near(int $cityId, string $coordinate): float
    {
        return City::find($cityId)->{$coordinate} + $this->faker->randomFloat(4, -0.05, 0.05);
    }
}
