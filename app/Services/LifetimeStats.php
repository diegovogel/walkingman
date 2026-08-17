<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

final readonly class LifetimeStats
{
    public function __construct(
        public int $tripsCompleted,
        public float $milesWalked,
        public int $yearsWalking,
        public int $daysWalking,
        public int $citiesVisited,
        public int $statesVisited,
    ) {}

    /**
     * Everything the walking man has done so far, or null until he has finished
     * his first trip and there is anything to report.
     */
    public static function compile(): ?self
    {
        $totals = Trip::completed()
            ->selectRaw('count(*) as trips, coalesce(sum(distance), 0) as miles, min(departure) as first_departure')
            ->toBase()
            ->first();

        if ((int) $totals->trips === 0) {
            return null;
        }

        // Aggregates arrive raw, around the model's casts: the decimal sum as a
        // string on MySQL, the departure as whatever the column holds.
        $firstDeparture = Carbon::parse($totals->first_departure);
        $years = (int) $firstDeparture->diffInYears(now());

        $places = self::placesVisited();

        return new self(
            tripsCompleted: (int) $totals->trips,
            milesWalked: (float) $totals->miles,
            yearsWalking: $years,
            daysWalking: (int) $firstDeparture->copy()->addYears($years)->diffInDays(now()),
            citiesVisited: $places['cities'],
            statesVisited: $places['states'],
        );
    }

    /**
     * Counted across both ends of every completed trip, so that the very first
     * origin, which is nowhere else a destination, still counts as visited.
     *
     * @return array{cities: int, states: int}
     */
    private static function placesVisited(): array
    {
        $counts = Location::query()
            ->join('cities', 'cities.id', '=', 'locations.city_id')
            // Grouped so that a later clause on this query cannot leak past the or.
            ->where(function (Builder $query): void {
                $query->whereIn('locations.id', Trip::completed()->select('origin_location_id'))
                    ->orWhereIn('locations.id', Trip::completed()->select('destination_location_id'));
            })
            ->selectRaw('count(distinct locations.city_id) as cities')
            ->selectRaw('count(distinct cities.state_abbreviation) as states')
            ->toBase()
            ->first();

        return [
            'cities' => (int) $counts->cities,
            'states' => (int) $counts->states,
        ];
    }
}
