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

    public function updateProfile(object $payload)
    {
        $user = $payload->user;
        $user->first_name = $payload->first_name;
        $user->last_name = $payload->last_name;
        $user->email = $payload->email;
        $user->save();

        return $user;
    }

    public function updatePassword(object $payload)
    {
        $user = $payload->user;
        $user->password = $payload->password;
        $user->save();

        return $user;
    }

    public function markEmailAsVerified(object $payload)
    {
        $user = $payload->user;
        $user->markEmailAsVerified();

        return $user;
    }
}
