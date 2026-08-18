<?php

namespace Modules\Tasks\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Projects\Models\Project;
use Modules\Tasks\Enums\TaskPriority;
use Modules\Tasks\Enums\TaskStatus;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number', 'project_id', 'creator_id', 'assignee_id', 'title', 'description',
        'status', 'priority', 'due_at', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_at' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'creator_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assignee_id'); }
    public function comments(): HasMany { return $this->hasMany(TaskComment::class); }
    public function attachments(): HasMany { return $this->hasMany(TaskAttachment::class); }
}
