<?php

namespace App\Enums;

enum GeocodioAccuracyType: string
{
    case Rooftop = 'rooftop';
    case Point = 'point';
    case RangeInterpolation = 'range_interpolation';
    case NearestRooftopMatch = 'nearest_rooftop_match';
    case Intersection = 'intersection';
    case StreetCenter = 'street_center';
    case NearestStreet = 'nearest_street';
    case NearestPlace = 'nearest_place';
    case Place = 'place';
    case County = 'county';
    case State = 'state';

    /**
     * Types precise enough to trust as a real, deliverable street address.
     * Notably absent: nearest_street, where Geocodio estimates a house number,
     * producing a fabricated address that looks real.
     *
     * @return array<string>
     */
    public static function deliverable(): array
    {
        return [
            self::Rooftop->value,
            self::Point->value,
            self::RangeInterpolation->value,
            self::NearestRooftopMatch->value,
        ];
    }
}
