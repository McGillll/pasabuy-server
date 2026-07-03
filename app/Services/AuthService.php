<?php

namespace App\Services;

use App\Exceptions\AuthenticationFailedException;
use App\Interfaces\Auth\AuthRepositoryInterface;
use App\Interfaces\Auth\AuthServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        protected AuthRepositoryInterface $authRepository,
    ) {}

    public function login(object $payload)
    {
        $user = $this->authRepository->findByEmail($payload->email);

        if (! $user || ! Hash::check($payload->password, $user->password)) {
            throw new AuthenticationFailedException('These credentials do not match our records.');
        }

        $expiresAt = ($payload->remember ?? false)
            ? Carbon::now()->addDays(30)
            : Carbon::now()->addHours(12);

        $token = $user->createToken('auth_token', [$user->role], $expiresAt)->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(object $payload)
    {
        $payload->user->currentAccessToken()->delete();
    }

    public function me(object $payload)
    {
        return $payload->user;
    }
}
