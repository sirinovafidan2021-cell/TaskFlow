<?php

namespace Modules\Dashboard\Livewire;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Projects\Models\Project;
use Modules\Tasks\Data\CreateTaskData;
use Modules\Tasks\Enums\TaskPriority;
use Modules\Tasks\Enums\TaskType;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Services\QuickTaskCreateService;

class QuickTaskCreate extends Component
{
    #[Locked]
    public ?int $fixedProjectId = null;

    public ?int $projectId = null;
    public string $title = '';
    public string $type = 'task';
    public string $priority = 'medium';
    public ?int $assigneeId = null;
    public ?int $parentId = null;
    /** @var list<int> */
    public array $labelIds = [];
    public ?string $success = null;

    public function mount(?Project $project = null): void
    {
        if ($project === null) {
            return;
        }

        $this->authorize('create', [Task::class, $project]);
        $this->fixedProjectId = $project->id;
        $this->projectId = $project->id;
    }

    public function updatedProjectId(): void
    {
        if ($this->fixedProjectId !== null) {
            $this->projectId = $this->fixedProjectId;
        }

        $this->assigneeId = null;
        $this->parentId = null;
        $this->labelIds = [];
        $this->resetValidation(['projectId', 'assigneeId', 'parentId', 'labelIds']);
    }

    public function submit(QuickTaskCreateService $quickTasks): void
    {
        $this->validate();

        try {
            $project = $quickTasks->project((int) $this->projectId);
            $this->authorize('create', [Task::class, $project]);
            $task = $quickTasks->create(auth()->user(), $project, new CreateTaskData(
                $project->id,
                $this->title,
                null,
                $this->assigneeId,
                TaskPriority::from($this->priority),
                null,
                TaskType::from($this->type),
                $this->parentId,
                $this->labelIds,
            ));
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError(match ($field) {
                    'label_ids' => 'labelIds',
                    'assignee_id' => 'assigneeId',
                    'parent_id' => 'parentId',
                    default => $field,
                }, $messages[0]);
            }

            return;
        } catch (ModelNotFoundException|\LogicException $exception) {
            $this->addError($this->invalidField(), $exception->getMessage());

            return;
        }

        $this->reset(['title', 'type', 'priority', 'assigneeId', 'parentId', 'labelIds']);
        $this->type = TaskType::Task->value;
        $this->priority = TaskPriority::Medium->value;
        $this->projectId = $this->fixedProjectId;
        $this->success = "{$task->display_key} created in Backlog.";
        $this->resetValidation();
    }

    public function render(QuickTaskCreateService $quickTasks)
    {
        $projects = $quickTasks->projectsFor(auth()->user());
        $project = $this->projectId && $projects->contains('id', $this->projectId)
            ? $quickTasks->project($this->projectId)
            : null;

        return view('dashboard::livewire.quick-task-create', [
            'projects' => $projects,
            'project' => $project,
            'options' => $project ? $quickTasks->optionsFor($project) : ['memberships' => collect(), 'labels' => collect(), 'parents' => collect()],
            'priorities' => TaskPriority::cases(),
            'types' => TaskType::cases(),
        ]);
    }

    protected function rules(): array
    {
        return [
            'projectId' => ['required', 'integer'],
            'title' => ['required', 'string', 'min:3', 'max:180'],
            'type' => ['required', Rule::enum(TaskType::class)],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'assigneeId' => ['nullable', 'integer'],
            'parentId' => ['nullable', 'integer'],
            'labelIds' => ['array'],
            'labelIds.*' => ['integer', 'distinct'],
        ];
    }

    private function invalidField(): string
    {
        return match (true) {
            $this->parentId !== null => 'parentId',
            $this->assigneeId !== null => 'assigneeId',
            $this->labelIds !== [] => 'labelIds',
            default => 'projectId',
        };
    }
}
