<?php

namespace App\Models;

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
}
