<?php

namespace App\Repositories;

use App\Interfaces\Auth\AuthRepositoryInterface;
use App\Models\User;

class AuthRepository implements AuthRepositoryInterface
{
    public function findByEmail(string $email)
    {
        return User::where('email', $email)->first();
    }

    public function findByUuid(string $uuid)
    {
        return User::where('uuid', $uuid)->first();
    }
}
