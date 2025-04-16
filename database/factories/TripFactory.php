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

        // Each trip starts where the last one ended.
        if ($previousTrip) {
            $originCity = $previousTrip->destination_city_id;
        } else {
            $originCity = City::inRandomOrder()->first();
        }

        $destinationCity = City::inRandomOrder()->first();

        // We don't want the origin and destination to be the same.
        while ($originCity->id === $destinationCity->id) {
            $destinationCity = City::inRandomOrder()->first();
        }

        $distance = Trip::calculateDistance($originCity, $destinationCity);

        $departure = Carbon::now();
        $arrival = Trip::calculateArrival($originCity, $destinationCity, $departure);

        $destinationCameFromUser = $this->faker->boolean();

        if ($destinationCameFromUser) {
            $user = User::inRandomOrder()->first();

            if (! $user) {
                $user = User::factory()->create();
            }
        } else {
            $user = null;
        }

        return [
            'distance' => $distance,
            'departure' => $departure,
            'arrival' => $arrival,
            'destination_from_user' => $destinationCameFromUser,
            'destination_is_random' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'origin_city_id' => $originCity,
            'destination_city_id' => $destinationCity,
            'user_id' => $user,
        ];
    }
}
