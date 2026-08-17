<?php

namespace App\Services;

use App\Models\Location;

readonly class PickedDestination
{
    public function __construct(
        public Location $location,
        public ?float $drivingDistanceMiles,
    ) {}
}
