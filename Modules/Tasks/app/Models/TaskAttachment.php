<?php

namespace Modules\Tasks\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Media\Models\Media;

class TaskAttachment extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return \Database\Factories\TaskAttachmentFactory::new();
    }
    // Legacy storage fields remain only until the preserved-data migration is verified.
    protected $fillable = ['task_id', 'media_id', 'uploaded_by', 'disk', 'path', 'original_name', 'mime_type', 'size'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
