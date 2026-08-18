<?php

namespace Modules\Projects\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Projects\Models\ProjectMember;

final class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'owner_id',
        'starts_at',
        'due_at',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'due_at' => 'datetime',
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }
}
