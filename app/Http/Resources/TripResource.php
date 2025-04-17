<?php

namespace App\Http\Resources;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Trip */ class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'distance' => $this->distance,
            'departure' => $this->departure,
            'arrival' => $this->arrival,
            'destination_from_user' => $this->destination_from_user,
            'destination_is_random' => $this->destination_is_random,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'origin_city_id' => $this->origin_city_id,
            'destination_city_id' => $this->destination_city_id,
            'user_id' => $this->user_id,

            'originCity' => new CityResource($this->whenLoaded('originCity')),
            'destinationCity' => new CityResource($this->whenLoaded('destinationCity')),
        ];
    }
}
