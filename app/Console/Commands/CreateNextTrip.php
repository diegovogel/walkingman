<?php

namespace App\Console\Commands;

use App\Models\Trip;
use App\Services\DestinationPicker;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CreateNextTrip extends Command
{
    protected $signature = 'trip:next';

    protected $description = 'Create the next trip, unless one is already underway';

    public function handle(DestinationPicker $picker): int
    {
        if (Trip::query()->underway()->exists()) {
            $this->info('A trip is already underway.');

            return self::SUCCESS;
        }

        $previousTrip = Trip::query()->latest('id')->first();
        $origin = $previousTrip?->destinationLocation ?? $picker->pick()->location;

        $destination = $picker->pick($origin);
        $distance = $destination->drivingDistanceMiles;

        if ($distance === null) {
            $distance = Trip::calculateDistance($origin, $destination->location);

            Log::warning('trip:next: falling back to straight-line distance', [
                'destination_location_id' => $destination->location->id,
            ]);
        }

        // Depart now rather than chaining to the previous trip's arrival: a
        // stale arrival would produce a trip that is already partly (or fully)
        // walked the moment it is created.
        $departure = Carbon::now();

        Trip::create([
            'origin_location_id' => $origin->id,
            'destination_location_id' => $destination->location->id,
            'distance' => $distance,
            'departure' => $departure,
            'arrival' => Trip::arrivalAfterWalking($distance, $departure),
            'destination_from_user' => false,
            'destination_is_random' => true,
        ]);

        $this->info("Next trip: {$destination->location->full_address} ({$distance} miles).");

        return self::SUCCESS;
    }
}
