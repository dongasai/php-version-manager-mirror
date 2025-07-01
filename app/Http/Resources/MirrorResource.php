<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MirrorResource extends JsonResource
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
            'name' => $this->name,
            'type' => $this->type,
            'type_name' => $this->type_name,
            'url' => $this->url,
            'status' => $this->status,
            'status_name' => $this->status_name,
            'is_enabled' => $this->isEnabled(),
            'config' => $this->config,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // 关联数据
            'latest_sync_job' => new SyncJobResource($this->whenLoaded('latestSyncJob')),
            'sync_jobs_count' => $this->when(isset($this->sync_jobs_count), $this->sync_jobs_count),

            // 统计信息
            'stats' => $this->when(isset($this->stats), $this->stats),
        ];
    }
}
