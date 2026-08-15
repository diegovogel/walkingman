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
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Location $location): void {
            // Keep the address within a few miles of its city, so that generated
            // trips cover realistic distances rather than spanning the globe.
            $location->latitude ??= $location->city->latitude + $this->faker->randomFloat(4, -0.05, 0.05);
            $location->longitude ??= $location->city->longitude + $this->faker->randomFloat(4, -0.05, 0.05);
        });
    }
}
