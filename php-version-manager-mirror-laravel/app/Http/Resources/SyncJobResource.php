<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SyncJobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mirror_id' => $this->mirror_id,
            'status' => $this->status,
            'status_name' => $this->status_name,
            'status_color' => $this->status_color,
            'progress' => $this->progress,
            'progress_percent' => $this->progress_percent,
            'log' => $this->when($request->has('include_log'), $this->log),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // 计算属性
            'duration' => $this->duration,
            'is_running' => $this->isRunning(),
            'is_completed' => $this->isCompleted(),
            'is_failed' => $this->isFailed(),
            'can_cancel' => $this->canCancel(),

            // 关联数据
            'mirror' => new MirrorResource($this->whenLoaded('mirror')),
        ];
    }
}
