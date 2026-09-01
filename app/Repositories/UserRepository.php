<?php
namespace App\Repositories;
use App\Models\User;
interface UserRepository { public function findOrFail(int $id): User; public function findByEmail(string $email): ?User; }
