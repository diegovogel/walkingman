<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'origin_city_id',
        'destination_city_id',
        'distance',
        'departure',
        'arrival',
        'destination_from_user',
        'destination_is_random',
        'user_id',
    ];

    public function originCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'origin_city_id');
    }

    public function destinationCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'destination_city_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'departure' => 'timestamp',
            'arrival' => 'timestamp',
            'destination_from_user' => 'boolean',
            'destination_is_random' => 'boolean',
        ];
    }

    /**
     * Calculates the distance between two cities using the Haversine formula.
     *
     * @param  City  $origin  An associative array containing the latitude and longitude of the origin city.
     * @param  City  $destination  An associative array containing the latitude and longitude of the destination city.
     * @return float The calculated distance in miles.
     */
    public static function calculateDistance(City $origin, City $destination): float
    {
        $lat1 = deg2rad($origin->latitude);
        $lon1 = deg2rad($origin->longitude);
        $lat2 = deg2rad($destination->latitude);
        $lon2 = deg2rad($destination->longitude);

        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;

        $a = sin($dLat / 2) * sin($dLat / 2) + cos($lat1) * cos($lat2) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * asin(sqrt($a));

        $r = 3958.8; // Radius of the earth in miles.

        return $c * $r;
    }

    public static function calculateArrival(City $origin, City $destination, ?Carbon $departure = null): Carbon
    {
        $departure = $departure ?? Carbon::now();
        $distance = self::calculateDistance($origin, $destination);
        $speed = config('app.walking_speed');
        $hours = $distance / $speed;

        return $departure->copy()->addHours($hours);
    }
}
