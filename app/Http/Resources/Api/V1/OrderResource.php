<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'table' => new TableResource($this->whenLoaded('table')),
            'restaurant_table_id' => $this->restaurant_table_id,
            'status' => $this->status,
            'queue_number' => $this->queue_number,
            'current_processing_queue_number' => $this->current_processing_queue_number,
            'note' => $this->note,
            'assigned_to' => $this->assignedUser?->name ?? $this->assigned_to,
            'assigned_to_user_id' => $this->assigned_to_user_id,
            'subtotal' => $this->subtotal,
            'service_charge' => $this->service_charge,
            'tax' => $this->tax,
            'total_price' => $this->total_price,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'notified_at' => $this->notified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
