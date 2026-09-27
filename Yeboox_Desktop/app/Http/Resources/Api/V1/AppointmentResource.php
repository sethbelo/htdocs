<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date_time' => $this->date_time?->toISOString(),
            'status' => $this->status,
            'notes' => $this->notes,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? ['id' => $this->customer->id, 'name' => $this->customer->name, 'phone' => $this->customer->phone] : null),
            'employee' => $this->whenLoaded('employee', fn () => $this->employee ? ['id' => $this->employee->id, 'name' => $this->employee->name] : null),
            'service' => $this->whenLoaded('service', fn () => $this->service ? ['id' => $this->service->id, 'name' => $this->service->name] : null),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
