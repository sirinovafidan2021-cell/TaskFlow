<?php

namespace App\Services;

use App\Data\CreateAdminUserData;
use App\Data\UpdateAdminUserData;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LogicException;

class AdminUserService
{
    public function paginate(?string $search, ?string $role, int $perPage = 12): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->withCount('projectMemberships')
            ->when(filled($search), function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(UserRole::tryFrom((string) $role), function ($query, UserRole $userRole): void {
                $query->role($userRole->value);
            })
            ->orderBy('name')
            ->orderBy('email')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(CreateAdminUserData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
            ]);

            $user->syncRoles([$data->role->value]);

            return $user;
        });
    }

    public function update(User $user, UpdateAdminUserData $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $this->ensureLastAdminIsRetained($user, $data->role);

            $user->fill([
                'name' => $data->name,
                'email' => $data->email,
            ]);
            $user->save();
            $user->syncRoles([$data->role->value]);

            return $user->fresh('roles');
        });
    }

    private function ensureLastAdminIsRetained(User $user, UserRole $nextRole): void
    {
        if ($nextRole === UserRole::Admin || ! $user->hasRole(UserRole::Admin->value)) {
            return;
        }

        if (User::role(UserRole::Admin->value)->count() <= 1) {
            throw new LogicException('The last Administrator cannot be assigned another global role.');
        }
    }
}
