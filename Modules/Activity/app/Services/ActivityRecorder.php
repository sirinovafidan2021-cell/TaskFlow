<?php

namespace Modules\Activity\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityRecorder
{
    public function record(string $event, User $actor, Model $subject, array $properties = []): void
    {
        activity($event)->causedBy($actor)->performedOn($subject)->withProperties($properties)->event($event)->log($event);
    }
}
