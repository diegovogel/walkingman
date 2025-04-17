<?php

namespace App\Http\Resources;

use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Game */ class GameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'short_description' => $this->short_description,
            'max_possible_points' => $this->max_possible_points,
            'path_to_description' => $this->path_to_description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
