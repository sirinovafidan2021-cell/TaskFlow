<?php

namespace Modules\Tasks\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tasks\Database\Factories\TaskAttachmentFactory;

class TaskAttachment extends Model
{
    use HasFactory;

    protected $fillable = ['task_id', 'uploaded_by', 'disk', 'path', 'original_name', 'mime_type', 'size'];

    protected static function newFactory(): TaskAttachmentFactory
    {
        return TaskAttachmentFactory::new();
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
