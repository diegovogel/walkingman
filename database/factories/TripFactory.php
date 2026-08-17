<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Location;
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
            $originLocation = $previousTrip->destinationLocation;
            $departure = $previousTrip->arrivesAt();
        } else {
            $originLocation = $this->locationInRandomCity();
            $departure = Carbon::now();
        }

        // We don't want the origin and destination to be in the same city.
        $destinationLocation = $this->locationInRandomCity(excluding: $originLocation->city_id);

        $distance = Trip::calculateDistance($originLocation, $destinationLocation);

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
            // Resolved after any state has had its say, so that a state moving
            // the departure gets an arrival to match without restating this.
            'arrival' => fn (array $attributes) => Trip::calculateArrival(
                $originLocation,
                $destinationLocation,
                $attributes['departure'],
            ),
            'destination_from_user' => $destinationCameFromUser,
            'destination_is_random' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'origin_location_id' => $originLocation,
            'destination_location_id' => $destinationLocation,
            'user_id' => $user,
        ];
    }

    /**
     * Departs at the given moment rather than now, so a chain can be started in
     * the past. Later trips follow on their own, each leaving when the one
     * before it arrives.
     */
    public function departingAt(Carbon $departure): static
    {
        return $this->state(['departure' => $departure]);
    }

    /**
     * Builds a location in one of the seeded cities, generating a city of its
     * own only when none have been seeded.
     */
    private function locationInRandomCity(?int $excluding = null): Location
    {
        $city = City::query()
            ->when($excluding, fn ($query) => $query->whereKeyNot($excluding))
            ->inRandomOrder()
            ->first();

        return Location::factory()->for($city ?? City::factory())->create();
    }
}
