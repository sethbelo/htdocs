<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegisterSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'opened_at' => $this->opened_at?->toISOString(),
            'closed_at' => $this->closed_at?->toISOString(),
            'currency' => $this->base_currency,
            'opening_balance' => (float) $this->opening_balance,
            'closing_balance' => (float) $this->closing_balance,
            'secondary_currency' => $this->secondary_currency,
            'base_to_secondary_rate' => (float) $this->base_to_secondary_rate,
            'note' => $this->note,
            'user' => $this->whenLoaded('user', fn () => ['id' => $this->user->id, 'name' => $this->user->name]),
        ];
    }
}
