<?php

namespace Modules\Activity\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\Activity\Enums\ActivityEvent;

class ActivityRecorder
{
    public function record(ActivityEvent $event, User $actor, Model $subject, array $properties = []): void
    {
        $properties = $this->sanitize($properties);
        $properties['schema_version'] = 1;
        activity($event->value)->causedBy($actor)->performedOn($subject)->withProperties($properties)->event($event->value)->log($event->value);
    }

    /** @param array<string, mixed> $properties @return array<string, mixed> */
    public function sanitize(array $properties): array
    {
        $safe = [];
        foreach ($properties as $key => $value) {
            if (preg_match('/password|token|secret|authorization|cookie|hash|credential|path|checksum|content|body|description|disk/i', (string) $key)) continue;
            $safe[$key] = is_array($value) ? $this->sanitize($value) : $value;
        }
        return $safe;
    }
}
