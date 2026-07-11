<?php

namespace App\Interfaces\Auth;

interface AuthRepositoryInterface
{
    public function findByEmail(string $email);

    public function findByUuid(string $uuid);

    public function updateProfile(object $payload);

    public function updatePassword(object $payload);
}
