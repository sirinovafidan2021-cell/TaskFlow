<?php

namespace Modules\Tasks\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tasks\Database\Factories\TaskCommentFactory;

class TaskComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['task_id', 'user_id', 'body'];

    protected static function newFactory(): TaskCommentFactory
    {
        return TaskCommentFactory::new();
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
