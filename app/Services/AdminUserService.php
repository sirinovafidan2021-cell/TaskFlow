<?php

namespace App\Services;

use App\Data\CreateAdminUserData;
use App\Data\ChangeOwnPasswordData;
use App\Data\ResetAdminUserPasswordData;
use App\Data\UpdateAdminUserData;
use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LogicException;
use Modules\Tasks\Repositories\TaskRepository;
use Modules\Tasks\Repositories\TaskWatcherRepository;

class AdminUserService
{
    public function __construct(private readonly TaskRepository $tasks, private readonly TaskWatcherRepository $watchers, private readonly SecurityAuditService $audit) {}
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

    public function create(CreateAdminUserData $data, ?User $actor = null): User
    {
        return DB::transaction(function () use ($data, $actor): User {
            $user = new User([
                'name' => $data->name,
                'email' => $this->normalizeEmail($data->email),
                'password' => Hash::make($data->password),
            ]);
            $user->forceFill(['status' => AccountStatus::Active])->save();

            $user->syncRoles([$data->role->value]);
            $this->audit->record($actor ?: $user, $user, 'user.created', [
                'user_id' => $user->id,
                'role' => $data->role->value,
                'status' => AccountStatus::Active->value,
            ]);

            return $user;
        });
    }

    public function update(User $user, UpdateAdminUserData $data, ?User $actor = null): User
    {
        return DB::transaction(function () use ($user, $data, $actor): User {
            $this->ensureLastActiveAdminIsRetained($user, $data->role);
            $oldRole = $user->getRoleNames()->first();

            $user->fill([
                'name' => $data->name,
                'email' => $this->normalizeEmail($data->email),
            ]);
            $user->save();
            $user->syncRoles([$data->role->value]);
            $this->audit->record($actor ?: $user, $user, 'user.updated', [
                'user_id' => $user->id,
                'old_role' => $oldRole,
                'new_role' => $data->role->value,
            ]);

            return $user->fresh('roles');
        });
    }

    public function suspend(User $user, User $actor): User
    {
        return DB::transaction(function () use ($user, $actor): User {
            if (! $user->isActive()) {
                return $user;
            }

            $this->ensureLastActiveAdminCanBeSuspended($user);
            $this->tasks->unassignOpenWorkFor($user);
            $this->watchers->removeForUser($user);
            $user->tokens()->delete();
            $this->invalidateSessions($user);
            $user->forceFill(['status' => AccountStatus::Suspended])->save();

            $this->audit->record($actor, $user, 'user.suspended', ['user_id' => $user->id]);

            return $user->fresh('roles');
        });
    }

    public function reactivate(User $user, User $actor): User
    {
        return DB::transaction(function () use ($user, $actor): User {
            $user->forceFill(['status' => AccountStatus::Active])->save();
            $this->audit->record($actor, $user, 'user.reactivated', ['user_id' => $user->id]);

            return $user->fresh('roles');
        });
    }

    public function resetPassword(User $user, ResetAdminUserPasswordData $data, User $actor): void
    {
        DB::transaction(function () use ($user, $data, $actor): void {
            $user->forceFill(['password' => Hash::make($data->password), 'remember_token' => Str::random(60)])->save();
            $user->tokens()->delete();
            $this->invalidateSessions($user);
            $this->audit->record($actor, $user, 'user.password_reset', ['user_id' => $user->id]);
        });
    }

    public function changeOwnPassword(User $user, ChangeOwnPasswordData $data, string $currentSessionId): void
    {
        DB::transaction(function () use ($user, $data, $currentSessionId): void {
            $user->forceFill(['password' => Hash::make($data->password), 'remember_token' => Str::random(60)])->save();
            $user->tokens()->delete();
            $this->invalidateSessions($user, $currentSessionId);
            $this->audit->record($user, $user, 'user.password_changed', ['user_id' => $user->id]);
        });
    }

    private function ensureLastActiveAdminIsRetained(User $user, UserRole $nextRole): void
    {
        if ($nextRole === UserRole::Admin || ! $user->hasRole(UserRole::Admin->value)) {
            return;
        }

        if (User::role(UserRole::Admin->value)->where('status', AccountStatus::Active->value)->lockForUpdate()->count() <= 1) {
            throw new LogicException('The last Administrator cannot be assigned another global role.');
        }
    }

    private function ensureLastActiveAdminCanBeSuspended(User $user): void
    {
        if ($user->hasRole(UserRole::Admin->value)
            && User::role(UserRole::Admin->value)->where('status', AccountStatus::Active->value)->lockForUpdate()->count() <= 1) {
            throw new LogicException('The last active Administrator cannot be suspended.');
        }
    }

    private function invalidateSessions(User $user, ?string $except = null): void
    {
        if (! Schema::hasTable('sessions')) {
            return;
        }

        $query = DB::table('sessions')->where('user_id', $user->id);
        if ($except !== null) {
            $query->where('id', '!=', $except);
        }
        $query->delete();
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }
}
