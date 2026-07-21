<?php

namespace App\Interfaces\Auth;

interface AuthRepositoryInterface
{
    public function findByEmail(string $email);

    public function createEmailVerificationToken(object $payload);

    public function findValidEmailVerificationToken(object $payload);

    public function deleteEmailVerificationTokens(object $payload);

    public function updateProfile(object $payload);

    public function updatePassword(object $payload);

    public function markEmailAsVerified(object $payload);
}
