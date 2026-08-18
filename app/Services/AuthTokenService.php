<?php

namespace App\Services;

use App\Data\Auth\LoginData;
use App\Data\Auth\RegisterUserData;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;
use Spatie\Permission\Models\Role;

class AuthTokenService
{
    public function register(RegisterUserData $data): NewAccessToken
    {
        $user = DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
            ]);

            $memberRole = Role::query()
                ->where('name', UserRole::Member->value)
                ->where('guard_name', 'web')
                ->first();

            if ($memberRole !== null) {
                $user->assignRole($memberRole);
            }

            return $user;
        });

        event(new Registered($user));

        return $user->createToken($data->deviceName, ['*']);
    }

    public function login(LoginData $data): ?NewAccessToken
    {
        $user = User::query()->where('email', $data->email)->first();

        if ($user === null || ! Hash::check($data->password, $user->password)) {
            return null;
        }

        return $user->createToken($data->deviceName, ['*']);
    }

    public function revokeCurrentToken(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
