<?php

namespace Modules\Dashboard\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'active_projects' => $this['activeProjects'],
            'archived_projects' => $this['archivedProjects'],
            'total_tasks' => $this['totalTasks'],
            'todo' => $this['todo'],
            'in_progress' => $this['inProgress'],
            'review' => $this['review'],
            'overdue' => $this['overdue'],
            'completed_today' => $this['completedToday'],
            'project_status_distribution' => $this['projectStatusDistribution'],
            'task_type_distribution' => $this['taskTypeDistribution'],
        ];
    }
}
