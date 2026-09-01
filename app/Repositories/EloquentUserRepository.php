<?php
namespace App\Repositories;
use App\Models\User;
class EloquentUserRepository implements UserRepository { public function findOrFail(int $id): User { return User::query()->findOrFail($id); } public function findByEmail(string $email): ?User { return User::query()->where('email', $email)->first(); } }
