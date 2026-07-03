<?php

namespace App\Interfaces\Auth;

interface AuthServiceInterface
{
    public function login(object $payload);

    public function logout(object $payload);

    public function me(object $payload);
}
