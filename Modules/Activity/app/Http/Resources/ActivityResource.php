<?php

namespace Modules\Activity\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'event' => $this->event, 'description' => $this->description, 'subject_type' => $this->subject_type, 'subject_id' => $this->subject_id, 'causer' => $this->whenLoaded('causer', fn () => $this->causer ? ['id' => $this->causer->id, 'name' => $this->causer->name, 'email' => $this->causer->email] : null), 'properties' => $this->properties?->except(['password', 'token', 'secret'])->all(), 'created_at' => $this->created_at?->toISOString()];
    }
}
