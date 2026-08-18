<?php

namespace Modules\Projects\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'slug' => $this->slug, 'description' => $this->description, 'status' => $this->status->value, 'starts_at' => $this->starts_at?->toDateString(), 'due_at' => $this->due_at?->toDateString(), 'owner' => $this->whenLoaded('owner', fn () => ['id' => $this->owner->id, 'name' => $this->owner->name, 'email' => $this->owner->email]), 'members_count' => $this->whenCounted('memberships'), 'created_at' => $this->created_at?->toISOString(), 'updated_at' => $this->updated_at?->toISOString()];
    }
}
