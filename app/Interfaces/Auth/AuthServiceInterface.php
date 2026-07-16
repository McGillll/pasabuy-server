<?php

namespace App\Interfaces\Auth;

interface AuthServiceInterface
{
    public function login(object $payload);

    public function logout(object $payload);

    public function me(object $payload);

    public function updateProfile(object $payload);

    public function updatePassword(object $payload);

    public function forgotPassword(object $payload);

    public function resetPassword(object $payload);

    public function sendVerificationEmail(object $payload);

    public function verifyEmail(object $payload);
}
