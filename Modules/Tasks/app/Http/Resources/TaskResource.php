<?php

namespace Modules\Tasks\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'number' => $this->number, 'project_id' => $this->project_id, 'creator_id' => $this->creator_id, 'assignee_id' => $this->assignee_id, 'title' => $this->title, 'description' => $this->description, 'status' => $this->status->value, 'priority' => $this->priority->value, 'due_at' => $this->due_at?->toDateString(), 'started_at' => $this->started_at?->toISOString(), 'completed_at' => $this->completed_at?->toISOString(), 'project' => $this->whenLoaded('project', fn () => ['id' => $this->project->id, 'name' => $this->project->name, 'slug' => $this->project->slug]), 'creator' => $this->whenLoaded('creator', fn () => ['id' => $this->creator->id, 'name' => $this->creator->name, 'email' => $this->creator->email]), 'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee ? ['id' => $this->assignee->id, 'name' => $this->assignee->name, 'email' => $this->assignee->email] : null), 'created_at' => $this->created_at?->toISOString(), 'updated_at' => $this->updated_at?->toISOString()];
    }
}
