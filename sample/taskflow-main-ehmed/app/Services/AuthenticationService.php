<?php

namespace App\Services;

use App\Data\CreatePersonalAccessTokenData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

final class AuthenticationService
{
    /**
     * @var list<string>
     */
    private const TOKEN_ABILITIES = [
        'projects:read',
        'projects:write',
        'tasks:read',
        'tasks:write',
        'comments:write',
        'activity:read',
        'dashboard:read',
    ];

    public function createPersonalAccessToken(CreatePersonalAccessTokenData $data): NewAccessToken
    {
        $user = User::query()->where('email', $data->email)->first();

        if ($user === null || ! Hash::check($data->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return $user->createToken($data->deviceName, self::TOKEN_ABILITIES);
    }

    /**
     * @return list<string>
     */
    public function tokenAbilities(): array
    {
        return self::TOKEN_ABILITIES;
    }

    public function revokeCurrentAccessToken(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
