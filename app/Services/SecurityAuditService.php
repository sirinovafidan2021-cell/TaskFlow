<?php

namespace App\Services;

use App\Models\User;

class SecurityAuditService
{
    /** @var list<string> */
    private const SENSITIVE_KEY_PARTS = ['password', 'token', 'secret', 'authorization', 'cookie', 'hash', 'credential'];

    /** @param array<string, mixed> $properties */
    public function record(User $actor, User $subject, string $event, array $properties = []): void
    {
        activity($event)
            ->causedBy($actor)
            ->performedOn($subject)
            ->withProperties($this->sanitize($properties))
            ->event($event)
            ->log($event);
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
