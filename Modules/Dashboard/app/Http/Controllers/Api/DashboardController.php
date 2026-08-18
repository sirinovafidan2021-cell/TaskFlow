<?php

namespace Modules\Dashboard\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Activity\Http\Resources\ActivityResource;
use Modules\Dashboard\Services\DashboardService;
use Modules\Tasks\Http\Resources\TaskResource;

class DashboardController
{
    use AuthorizesRequests;

    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewDashboard');
        $summary = $this->dashboard->summary($request->user());

        return response()->json(['success' => true, 'message' => 'Dashboard retrieved successfully.', 'data' => ['active_projects' => $summary['activeProjects'], 'archived_projects' => $summary['archivedProjects'], 'total_tasks' => $summary['totalTasks'], 'todo' => $summary['todo'], 'in_progress' => $summary['inProgress'], 'review' => $summary['review'], 'overdue' => $summary['overdue'], 'completed_today' => $summary['completedToday'], 'my_tasks' => TaskResource::collection($summary['myTasks']), 'recent_activity' => ActivityResource::collection($summary['recentActivity'])]]);
    }
}
