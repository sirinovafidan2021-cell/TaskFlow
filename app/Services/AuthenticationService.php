<?php

namespace App\Services;

use App\Data\CreatePersonalAccessTokenData;
use App\Models\User;
use Modules\Activity\Enums\ActivityEvent;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

class AuthenticationService
{
    public function __construct(private readonly UserRepository $users, private readonly SecurityAuditService $audit) {}

    public function createPersonalAccessToken(CreatePersonalAccessTokenData $data): ?NewAccessToken
    {
        $user = $this->users->findByEmail($data->email);

        if (! $user || ! $user->isActive() || ! Hash::check($data->password, $user->password)) {
            return null;
        }

        $token = $user->createToken($data->deviceName, $data->abilityValues());
        $this->audit->record($user, $user, ActivityEvent::ApiTokenIssued, [
            'user_id' => $user->id,
            'device_name' => $data->deviceName,
            'abilities' => $data->abilityValues(),
        ]);

        return $token;
    }

    public function revokeCurrentToken(User $user, ?string $plainTextToken): void
    {
        $token = $plainTextToken ? PersonalAccessToken::findToken($plainTextToken) : null;

        if ($token && $token->tokenable_type === $user::class && $token->tokenable_id === $user->id) {
            $token->delete();
            $this->audit->record($user, $user, ActivityEvent::ApiTokenRevoked, ['user_id' => $user->id]);
        }
    }
}
