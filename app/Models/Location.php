<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'street_address',
        'postal_code',
        'latitude',
        'longitude',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * The address written out in full, e.g. "1234 Main St, Minneapolis, MN 55401".
     */
    protected function fullAddress(): Attribute
    {
        return Attribute::get(fn (): string => collect([
            $this->street_address,
            $this->city->name,
            trim($this->city->state_abbreviation.' '.$this->postal_code),
        ])->implode(', '));
    }

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }
}
