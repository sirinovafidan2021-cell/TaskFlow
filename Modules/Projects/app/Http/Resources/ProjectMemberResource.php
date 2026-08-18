<?php

namespace Modules\Projects\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'member_role' => $this->member_role->value, 'joined_at' => $this->joined_at?->toISOString(), 'user' => ['id' => $this->user->id, 'name' => $this->user->name, 'email' => $this->user->email]];
    }
}
