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
        'origin_location_id',
        'destination_location_id',
        'distance',
        'departure',
        'arrival',
        'destination_from_user',
        'destination_is_random',
        'user_id',
    ];

    public function originLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'origin_location_id');
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_location_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function departedAt(): Carbon
    {
        return Carbon::createFromTimestamp($this->departure);
    }

    public function arrivesAt(): Carbon
    {
        return Carbon::createFromTimestamp($this->arrival);
    }

    /**
     * Miles left to walk, derived from the time left rather than stored, so it
     * stays consistent with the arrival time the trip was created with.
     */
    public function milesRemaining(): float
    {
        $hoursRemaining = Carbon::now()->diffInHours($this->arrivesAt(), absolute: false);

        return max(0, $hoursRemaining * config('app.walking_speed'));
    }

    /**
     * @return array{days: int, hours: int, minutes: int}
     */
    public function timeRemaining(): array
    {
        $minutes = (int) max(0, floor(Carbon::now()->diffInMinutes($this->arrivesAt(), absolute: false)));

        return [
            'days' => intdiv($minutes, 1440),
            'hours' => intdiv($minutes % 1440, 60),
            'minutes' => $minutes % 60,
        ];
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
     * Calculates the distance between two locations using the Haversine formula.
     *
     * @return float The calculated distance in miles.
     */
    public static function calculateDistance(Location $origin, Location $destination): float
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

    public static function calculateArrival(Location $origin, Location $destination, ?Carbon $departure = null): Carbon
    {
        $departure = $departure ?? Carbon::now();
        $distance = self::calculateDistance($origin, $destination);
        $speed = config('app.walking_speed');
        $hours = $distance / $speed;

        return $departure->copy()->addHours($hours);
    }
}
