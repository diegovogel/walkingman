<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Trip;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

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
        $firstTrip = Trip::completed()->oldest('departure')->first();

        if (! $firstTrip) {
            return null;
        }

        [$years, $days] = self::timeWalkingSince($firstTrip->departedAt());
        $places = self::placesVisited();

        return new self(
            tripsCompleted: Trip::completed()->count(),
            // Decimal sums come back as strings on MySQL and floats on SQLite.
            milesWalked: (float) Trip::completed()->sum('distance'),
            yearsWalking: $years,
            daysWalking: $days,
            citiesVisited: $places['cities'],
            statesVisited: $places['states'],
        );
    }

    /**
     * @return array{0: int, 1: int} Whole years, then the days left over.
     */
    private static function timeWalkingSince(CarbonInterface $start): array
    {
        $years = (int) $start->diffInYears(now());

        return [$years, (int) $start->copy()->addYears($years)->diffInDays(now())];
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
