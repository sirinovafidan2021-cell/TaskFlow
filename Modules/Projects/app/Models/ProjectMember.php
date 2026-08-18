<?php

namespace Modules\Projects\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Projects\Database\Factories\ProjectMemberFactory;
use Modules\Projects\Enums\ProjectMemberRole;

class ProjectMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'member_role',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'member_role' => ProjectMemberRole::class,
            'joined_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ProjectMemberFactory
    {
        return ProjectMemberFactory::new();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
