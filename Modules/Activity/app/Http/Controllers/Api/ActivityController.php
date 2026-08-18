<?php

namespace Modules\Activity\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\Activity\Http\Requests\Api\ActivityIndexRequest;
use Modules\Activity\Http\Resources\ActivityResource;
use Modules\Activity\Services\ActivityQueryService;
use Spatie\Activitylog\Models\Activity;

class ActivityController
{
    use AuthorizesRequests;

    public function __construct(private readonly ActivityQueryService $activity) {}

    public function index(ActivityIndexRequest $request)
    {
        $this->authorize('viewAny', Activity::class);

        return ActivityResource::collection($this->activity->paginate($request->user(), $request->validated()))->additional(['success' => true, 'message' => 'Activities retrieved successfully.']);
    }
}
