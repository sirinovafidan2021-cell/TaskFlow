<?php

namespace Modules\Tasks\Repositories;

use Illuminate\Support\Collection;
use Modules\Projects\Models\Project;
use Modules\Tasks\Models\TaskLabel;

class EloquentTaskLabelRepository implements TaskLabelRepository
{
    public function forProject(Project $project): Collection
    {
        return TaskLabel::query()->where('project_id', $project->id)->orderBy('name')->get();
    }

    public function save(TaskLabel $label): TaskLabel
    {
        $label->save();

        return $label;
    }

    public function delete(TaskLabel $label): void
    {
        $label->delete();
    }

    public function countForProject(Project $project, array $ids): int
    {
        return TaskLabel::query()->where('project_id', $project->id)->whereIn('id', $ids)->count();
    }
}
