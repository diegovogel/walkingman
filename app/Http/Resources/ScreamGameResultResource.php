<?php

namespace App\Http\Resources;

use App\Models\ScreamGameResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ScreamGameResult */ class ScreamGameResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'media_id' => $this->media_id,
            'loudness' => $this->loudness,
            'phrase_was_spoken' => $this->phrase_was_spoken,
            'performed_in_public' => $this->performed_in_public,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
