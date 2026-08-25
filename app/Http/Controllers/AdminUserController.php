<?php

namespace App\Http\Controllers;

use App\Data\CreateAdminUserData;
use App\Data\UpdateAdminUserData;
use App\Enums\UserRole;
use App\Http\Requests\Admin\IndexAdminUserRequest;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Models\User;
use App\Services\AdminUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use LogicException;

class AdminUserController extends Controller
{
    public function __construct(private readonly AdminUserService $users) {}

    public function index(IndexAdminUserRequest $request): View
    {
        return view('admin.users.index', [
            'users' => $this->users->paginate(
                $request->string('search')->trim()->toString(),
                $request->string('role')->toString(),
            ),
            'roles' => UserRole::cases(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', ['roles' => UserRole::cases()]);
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $user = $this->users->create(CreateAdminUserData::fromArray($request->validated()));

        return redirect()->route('admin.users.edit', $user)
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'managedUser' => $user->load('roles'),
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(UpdateAdminUserRequest $request, User $user): RedirectResponse
    {
        try {
            $this->users->update($user, UpdateAdminUserData::fromArray($request->validated()));
        } catch (LogicException $exception) {
            throw ValidationException::withMessages(['role' => $exception->getMessage()]);
        }

        return redirect()->route('admin.users.edit', $user)
            ->with('success', 'User details and global role updated.');
    }
}
