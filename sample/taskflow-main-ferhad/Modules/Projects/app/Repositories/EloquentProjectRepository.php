<?php

namespace Modules\Projects\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Models\Project;

class EloquentProjectRepository implements ProjectRepository
{
    public function paginate(?string $search, ?string $status): LengthAwarePaginator
    {
        return Project::query()
            ->with('owner')
            ->when(filled($search), function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(ProjectStatus::tryFrom((string) $status), function ($query, ProjectStatus $projectStatus): void {
                $query->where('status', $projectStatus->value);
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();
    }

    public function save(Project $project): Project
    {
        $project->save();

        return $project;
    }

    public function slugExists(string $slug, ?int $excludingProjectId = null): bool
    {
        return Project::query()
            ->where('slug', $slug)
            ->when($excludingProjectId, fn ($query) => $query->where('id', '!=', $excludingProjectId))
            ->exists();
    }
}
