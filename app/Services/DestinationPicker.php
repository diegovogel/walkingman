<?php

namespace App\Services;

use App\Models\City;
use App\Models\Location;
use Geocodio\Enums\DistanceMode;
use Geocodio\Enums\DistanceUnits;
use Geocodio\Exceptions\GeocodioException;
use Geocodio\Geocodio;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DestinationPicker
{
    private const MAX_ATTEMPTS = 5;

    /**
     * Accuracy types precise enough to trust as a real, deliverable street
     * address. Notably absent: nearest_street, where Geocodio estimates a
     * house number, producing a fabricated address that looks real.
     */
    private const DELIVERABLE_ACCURACY_TYPES = [
        'rooftop',
        'point',
        'range_interpolation',
        'nearest_rooftop_match',
    ];

    private const MINIMUM_ACCURACY = 0.8;

    private const RADIUS_MILES_PER_SQRT_POPULATION = 0.011;

    private const MIN_RADIUS_MILES = 1.5;

    private const MAX_RADIUS_MILES = 10.0;

    private const MILES_PER_DEGREE = 69.0;

    public function __construct(private readonly Geocodio $geocodio) {}

    /**
     * Picks a random street address in a random city (never the origin's own
     * city), by reverse geocoding random points near the city's center. Falls
     * back to the bare city center when no attempt yields a usable address, so
     * a Geocodio outage still produces a walkable destination.
     */
    public function pick(?Location $origin = null): PickedDestination
    {
        $city = $this->randomCity(excluding: $origin?->city_id);
        $radiusMiles = $this->radiusFor($city);

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            [$latitude, $longitude] = $this->jitter($city, $radiusMiles);

            $candidate = $this->deliverableAddressAt($latitude, $longitude, $city, $attempt);

            if ($candidate === null) {
                continue;
            }

            $drivingDistanceMiles = null;

            if ($origin) {
                $drivingDistanceMiles = $this->drivingDistanceMiles(
                    $origin,
                    $candidate['location']['lat'],
                    $candidate['location']['lng'],
                );

                if ($drivingDistanceMiles === null) {
                    Log::info('DestinationPicker: rejected candidate', [
                        'reason' => 'no driving route from origin',
                        'city' => $city->name,
                        'attempt' => $attempt,
                    ]);

                    continue;
                }
            }

            return new PickedDestination(
                $this->locationFromCandidate($candidate, $city),
                $drivingDistanceMiles,
            );
        }

        return $this->cityCenterFallback($city, $origin);
    }

    private function randomCity(?int $excluding): City
    {
        $city = City::query()
            ->when($excluding, fn ($query) => $query->whereKeyNot($excluding))
            ->inRandomOrder()
            ->first();

        return $city ?? throw new RuntimeException('DestinationPicker: no cities have been seeded');
    }

    /**
     * How far from the city center to look for addresses. Population stands in
     * for area (which scales with population, hence the square root); the
     * figures in cities.json are metro-area counts, so the cap does the real
     * work for large cities.
     */
    private function radiusFor(City $city): float
    {
        return min(
            max(self::RADIUS_MILES_PER_SQRT_POPULATION * sqrt($city->population ?? 0), self::MIN_RADIUS_MILES),
            self::MAX_RADIUS_MILES,
        );
    }

    /**
     * A random point within the radius around the city center.
     *
     * @return array{float, float}
     */
    private function jitter(City $city, float $radiusMiles): array
    {
        $bearing = 2 * M_PI * $this->randomFraction();

        // Uniform in radius, not in area: the missing sqrt() is deliberate.
        // Clustering toward the center raises the odds of hitting a real
        // address; the textbook uniform-in-disc correction would hurt here.
        $distanceMiles = $radiusMiles * $this->randomFraction();

        $latitude = $city->latitude + ($distanceMiles * cos($bearing)) / self::MILES_PER_DEGREE;

        // A degree of longitude spans fewer miles at higher latitudes, so
        // without this correction the sampled disc flattens into an ellipse.
        $longitude = $city->longitude
            + ($distanceMiles * sin($bearing)) / (self::MILES_PER_DEGREE * cos(deg2rad($city->latitude)));

        return [$latitude, $longitude];
    }

    private function randomFraction(): float
    {
        return mt_rand() / mt_getrandmax();
    }

    /**
     * Reverse geocodes a point and returns the result only if it is a real,
     * deliverable address inside the expected state.
     *
     * @return array<string, mixed>|null
     */
    private function deliverableAddressAt(float $latitude, float $longitude, City $city, int $attempt): ?array
    {
        try {
            $response = $this->geocodio->reverse("{$latitude},{$longitude}", limit: 1);
        } catch (GeocodioException $exception) {
            Log::warning('DestinationPicker: reverse geocode failed', [
                'city' => $city->name,
                'attempt' => $attempt,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        $result = $response['results'][0] ?? null;
        $reason = $result === null ? 'no results' : $this->rejectionReason($result, $city);

        if ($reason !== null) {
            Log::info('DestinationPicker: rejected candidate', [
                'reason' => $reason,
                'city' => $city->name,
                'attempt' => $attempt,
            ]);

            return null;
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function rejectionReason(array $result, City $city): ?string
    {
        $accuracyType = $result['accuracy_type'] ?? null;
        $components = $result['address_components'] ?? [];

        if (! in_array($accuracyType, self::DELIVERABLE_ACCURACY_TYPES, true)) {
            return "accuracy_type '{$accuracyType}' is not a deliverable address";
        }

        if (($result['accuracy'] ?? 0) < self::MINIMUM_ACCURACY) {
            return 'accuracy score below threshold';
        }

        if (empty($components['number'])) {
            return 'no house number';
        }

        // The state check is the border guard: Geocodio also covers Canada and
        // Mexico, and a point jittered across the border (Detroit/Windsor,
        // El Paso/Juarez) comes back as a high-confidence foreign address that
        // the accuracy checks alone would wave through.
        if (($components['state_province'] ?? $components['state'] ?? null) !== $city->state_abbreviation) {
            return 'address is outside the city\'s state';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private function locationFromCandidate(array $candidate, City $city): Location
    {
        $components = $candidate['address_components'];

        return Location::create([
            'city_id' => $city->id,
            'street_address' => trim($components['number'].' '.($components['formatted_street'] ?? '')),
            'postal_code' => $components['postal_code'] ?? $components['zip'] ?? null,
            'latitude' => $candidate['location']['lat'],
            'longitude' => $candidate['location']['lng'],
        ])->setRelation('city', $city);
    }

    private function cityCenterFallback(City $city, ?Location $origin): PickedDestination
    {
        Log::warning('DestinationPicker: no deliverable address found, using the city center', [
            'city' => $city->name,
            'attempts' => self::MAX_ATTEMPTS,
        ]);

        $location = Location::create([
            'city_id' => $city->id,
            'street_address' => null,
            'postal_code' => null,
            'latitude' => $city->latitude,
            'longitude' => $city->longitude,
        ])->setRelation('city', $city);

        $drivingDistanceMiles = $origin
            ? $this->drivingDistanceMiles($origin, $location->latitude, $location->longitude)
            : null;

        return new PickedDestination($location, $drivingDistanceMiles);
    }

    private function drivingDistanceMiles(Location $origin, float $latitude, float $longitude): ?float
    {
        try {
            $response = $this->geocodio->distance(
                "{$origin->latitude},{$origin->longitude}",
                ["{$latitude},{$longitude}"],
                DistanceMode::Driving,
                DistanceUnits::Miles,
            );
        } catch (GeocodioException $exception) {
            Log::warning('DestinationPicker: distance lookup failed', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        $miles = $response['destinations'][0]['distance_miles'] ?? null;

        return $miles === null ? null : (float) $miles;
    }
}
