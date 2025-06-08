<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public static $wrap = 'task';
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'created_by' => $this->resource->created_by,
            'assigned_to' => $this->resource->assigned_to,
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'status' => $this->resource->status,
            'priority' => $this->resource->priority,
            'due_date' => $this->resource->due_date,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
