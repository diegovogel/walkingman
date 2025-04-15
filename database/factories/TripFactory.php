<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        $previousTrip = Trip::orderBy('id', 'desc')->first();

        if ($previousTrip) {
            $originCity = $previousTrip->destination_city_id;
        } else {
            $originCity = City::inRandomOrder()->first();
        }

        $destinationCity = City::inRandomOrder()->first();

        while ($originCity->id === $destinationCity->id) {
            $destinationCity = City::inRandomOrder()->first();
        }

        dd($originCity, $destinationCity);

        return [
            'distance' => $this->faker->randomFloat(),
            'departure' => Carbon::now(),
            'arrival' => Carbon::now(),
            'destination_from_user' => $this->faker->boolean(),
            'destination_is_random' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'origin_city_id' => City::factory(),
            'destination_city_id' => City::factory(),
            'user_id' => User::factory(),
        ];
    }
}
