<?php

namespace Modules\Projects\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Activity\Services\ActivityRecorder;
use Modules\Projects\Data\ProjectData;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Models\Project;
use Modules\Projects\Repositories\ProjectRepository;

class ProjectService
{
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly ProjectMemberService $members,
        private readonly ActivityRecorder $activity,
    ) {}

    public function create(User $actor, ProjectData $data): Project
    {
        return DB::transaction(function () use ($actor, $data): Project {
            $project = new Project([
                'name' => $data->name,
                'slug' => $this->uniqueSlug($data->name),
                'description' => $data->description,
                'status' => ProjectStatus::Draft,
                'owner_id' => $actor->id,
                'starts_at' => $data->startsAt,
                'due_at' => $data->dueAt,
            ]);

            $project = $this->projects->save($project);
            $this->members->addMember($project, $actor, ProjectMemberRole::Manager, actor: $actor);
            $this->activity->record('project.created', $actor, $project, ['project_id' => $project->id, 'project_name' => $project->name]);

            return $project;
        });
    }

    public function update(Project $project, ProjectData $data, User $actor): Project
    {
        return DB::transaction(function () use ($project, $data, $actor): Project {
            $project->fill([
                'name' => $data->name,
                'description' => $data->description,
                'starts_at' => $data->startsAt,
                'due_at' => $data->dueAt,
            ]);

            if ($project->isDirty('name')) {
                $project->slug = $this->uniqueSlug($data->name, $project);
            }

            $changed = array_keys($project->getDirty());
            $project = $this->projects->save($project);

            if ($changed !== []) {
                $this->activity->record('project.updated', $actor, $project, [
                    'project_id' => $project->id,
                    'changed' => $changed,
                ]);
            }

            return $project;
        });
    }

    public function archive(Project $project, User $actor): Project
    {
        return DB::transaction(function () use ($project, $actor): Project {
            if ($project->status === ProjectStatus::Archived) {
                return $project;
            }

            $project->status = ProjectStatus::Archived;

            $project = $this->projects->save($project);
            $this->activity->record('project.archived', $actor, $project, ['project_id' => $project->id]);

            return $project;
        });
    }

    public function activate(Project $project, User $actor): Project
    {
        return DB::transaction(function () use ($project, $actor): Project {
            if ($project->status === ProjectStatus::Active) {
                return $project;
            }

            if ($project->status !== ProjectStatus::Draft) {
                throw new \LogicException('Only draft projects can be activated.');
            }

            $project->status = ProjectStatus::Active;

            $project = $this->projects->save($project);
            $this->activity->record('project.activated', $actor, $project, ['project_id' => $project->id]);

            return $project;
        });
    }

    private function uniqueSlug(string $name, ?Project $excludingProject = null): string
    {
        $baseSlug = Str::slug($name) ?: 'project';
        $slug = $baseSlug;
        $suffix = 2;

        while ($this->projects->slugExists($slug, $excludingProject?->id)) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
