<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderPollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'restaurant_table_id' => $this->restaurant_table_id,
            'status' => $this->status,
            'notified_at' => $this->notified_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
