<?php

namespace Modules\Tasks\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Projects\Models\Project;
use Modules\Projects\Repositories\ProjectRepository;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Tasks\Data\CreateTaskData;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Repositories\TaskRepository;

class QuickTaskCreateService
{
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly ProjectMemberService $members,
        private readonly TaskRepository $tasks,
        private readonly TaskLabelService $labels,
        private readonly TaskService $taskService,
    ) {}

    /** @return Collection<int, Project> */
    public function projectsFor(User $actor): Collection
    {
        return $this->projects->activeForTaskCreation($actor);
    }

    public function project(int $projectId): Project
    {
        return $this->projects->findOrFail($projectId);
    }

    /** @return array{memberships: Collection, labels: Collection, parents: Collection} */
    public function optionsFor(Project $project): array
    {
        return [
            'memberships' => $this->members->memberships($project),
            'labels' => $this->labels->forProject($project),
            'parents' => $this->tasks->standardParentsForProject($project),
        ];
    }

    public function create(User $actor, Project $project, CreateTaskData $data): Task
    {
        return $this->taskService->create($actor, $project, $data);
    }
}
