<?php

namespace App\Services;

use App\Models\User;
use Modules\Activity\Enums\ActivityEvent;
use Modules\Activity\Services\ActivityRecorder;

class SecurityAuditService
{
    /** @var list<string> */
    private const SENSITIVE_KEY_PARTS = ['password', 'token', 'secret', 'authorization', 'cookie', 'hash', 'credential'];

    /** @param array<string, mixed> $properties */
    public function __construct(private readonly ActivityRecorder $activity) {}

    public function record(User $actor, User $subject, ActivityEvent $event, array $properties = []): void
    {
        $this->activity->record($event, $actor, $subject, $this->sanitize($properties));
    }

    /** @param array<string, mixed> $properties
     *  @return array<string, mixed>
     */
    public function sanitize(array $properties): array
    {
        $sanitized = [];

        foreach ($properties as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                continue;
            }

            $sanitized[$key] = is_array($value) ? $this->sanitize($value) : $value;
        }

        return $sanitized;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach (self::SENSITIVE_KEY_PARTS as $part) {
            if (str_contains($normalized, $part)) {
                return true;
            }
        }

        return false;
    }
}
