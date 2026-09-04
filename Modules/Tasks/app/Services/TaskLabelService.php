<?php
namespace Modules\Tasks\Services;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use Modules\Activity\Services\ActivityRecorder;
use Modules\Activity\Enums\ActivityEvent;
use Modules\Tasks\Data\CreateTaskLabelData;
use Modules\Tasks\Data\UpdateTaskLabelData;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Models\Project;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskLabel;
use Modules\Tasks\Repositories\TaskLabelRepository;

class TaskLabelService
{
    public function __construct(private readonly ProjectMemberService $members, private readonly TaskLabelRepository $labels, private readonly ActivityRecorder $activity) {}

    /** @return Collection<int, TaskLabel> */
    public function forProject(Project $project): Collection { return $this->labels->forProject($project); }

    public function create(Project $project, CreateTaskLabelData $data, User $actor): TaskLabel
    {
        return DB::transaction(function () use ($project, $data, $actor): TaskLabel {
            $this->manage($project, $actor);
            $name = trim($data->name);
            $slug = $this->slug($name);
            $this->ensureUnique($project, $name, $slug);
            $label = $this->labels->save(new TaskLabel(['project_id' => $project->id, 'name' => $name, 'slug' => $slug, 'color' => $data->color->value]));
            $this->activity->record(ActivityEvent::LabelCreated, $actor, $label, ['project_id' => $project->id, 'label_id' => $label->id, 'label_name' => $label->name]);
            return $label;
        });
    }

    public function update(TaskLabel $label, UpdateTaskLabelData $data, User $actor): TaskLabel
    {
        return DB::transaction(function () use ($label, $data, $actor): TaskLabel {
            $this->manage($label->project, $actor);
            $name = trim($data->name);
            $slug = $this->slug($name);
            $this->ensureUnique($label->project, $name, $slug, $label);
            $label->fill(['name' => $name, 'slug' => $slug, 'color' => $data->color->value]);
            $label = $this->labels->save($label);
            $this->activity->record(ActivityEvent::LabelUpdated, $actor, $label, ['project_id' => $label->project_id, 'label_id' => $label->id, 'label_name' => $label->name]);
            return $label;
        });
    }

    /** @param list<int> $labelIds */
    public function sync(Task $task, array $labelIds, User $actor): Task
    {
        return DB::transaction(function () use ($task, $labelIds, $actor): Task {
            $project = $task->loadMissing('project')->project;
            if ($project->status !== ProjectStatus::Active || (! $this->members->canManage($project, $actor) && (! $this->members->isMember($project, $actor) || $task->creator_id !== $actor->id || ! in_array($task->status, [TaskStatus::Backlog, TaskStatus::Todo], true)))) {
                throw new LogicException('The actor cannot change task labels.');
            }
            $ids = array_values(array_unique($labelIds)); sort($ids);
            if ($this->labels->countForProject($project, $ids) !== count($ids)) throw ValidationException::withMessages(['label_ids' => ['Every label must belong to the task project.']]);
            $before = $task->labels()->pluck('task_labels.id')->sort()->values()->all();
            $task->labels()->sync($ids);
            if ($before !== $ids) $this->activity->record(ActivityEvent::TaskLabelsUpdated, $actor, $task, ['project_id' => $task->project_id, 'task_id' => $task->id, 'label_ids' => $ids]);
            return $task;
        });
    }

    public function delete(TaskLabel $label, User $actor): void
    {
        DB::transaction(function () use ($label, $actor): void {
            $this->manage($label->project, $actor);
            $projectId = $label->project_id; $labelId = $label->id; $name = $label->name;
            $this->labels->delete($label);
            $this->activity->record(ActivityEvent::LabelDeleted, $actor, $label, ['project_id' => $projectId, 'label_id' => $labelId, 'label_name' => $name]);
        });
    }

    private function slug(string $name): string { $slug = Str::slug($name); if ($slug === '') throw new LogicException('A label name must contain letters or numbers.'); return $slug; }
    private function ensureUnique(Project $project, string $name, string $slug, ?TaskLabel $ignore = null): void { $query = TaskLabel::query()->where('project_id', $project->id)->where(fn ($query) => $query->where('name', $name)->orWhere('slug', $slug)); if ($ignore) $query->whereKeyNot($ignore->id); if ($query->exists()) throw new LogicException('A project label name and slug must be unique.'); }
    private function manage(Project $project, User $actor): void { if ($project->status !== ProjectStatus::Active || ! $this->members->canManage($project, $actor)) throw new LogicException('The actor cannot manage project labels.'); }
}
