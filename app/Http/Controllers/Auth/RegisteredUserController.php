<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::query()->create([
                'name' => $request->string('name')->trim()->toString(),
                'email' => $request->string('email')->lower()->toString(),
                'password' => Hash::make($request->string('password')->toString()),
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

        Auth::login($user);
        $request->session()->regenerate();
        event(new Registered($user));

        return redirect()->route('verification.notice');
    }
}
