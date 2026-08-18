<?php

namespace Modules\Projects\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProjectMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'member_role',
        'joined_at',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }
}
