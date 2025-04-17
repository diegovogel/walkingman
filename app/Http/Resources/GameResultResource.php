<?php

namespace App\Http\Resources;

use App\Models\GameResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GameResult */ class GameResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'resultable_type' => $this->resultable_type,
            'resultable_id' => $this->resultable_id,
            'score' => $this->score,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'player_id' => $this->player_id,
            'game_id' => $this->game_id,

            'player' => new PlayerResource($this->whenLoaded('player')),
            'game' => new GameResource($this->whenLoaded('game')),
        ];
    }
}
