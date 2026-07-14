<?php

namespace App\Services;

use App\Exceptions\AuthenticationFailedException;
use App\Exceptions\InvalidPayloadException;
use App\Interfaces\Auth\AuthRepositoryInterface;
use App\Interfaces\Auth\AuthServiceInterface;
use App\Mail\PasswordResetMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

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

    public function updateProfile(object $payload)
    {
        return $this->authRepository->updateProfile($payload);
    }

    public function updatePassword(object $payload)
    {
        if (! Hash::check($payload->current_password, $payload->user->password)) {
            throw new AuthenticationFailedException('Current password is incorrect.');
        }

        return $this->authRepository->updatePassword($payload);
    }

    public function forgotPassword(object $payload)
    {
        $user = $this->authRepository->findByEmail($payload->email);

        if (! $user) {
            return;
        }

        $token = Password::broker()->createToken($user);
        $resetUrl = rtrim(config('app.frontend_url'), '/').'/reset-password?token='.$token.'&email='.urlencode($user->email);

        Mail::to($user->email)->queue(new PasswordResetMail($user, $resetUrl));
    }

    public function resetPassword(object $payload)
    {
        $status = Password::broker()->reset(
            [
                'email' => $payload->email,
                'password' => $payload->password,
                'password_confirmation' => $payload->password_confirmation,
                'token' => $payload->token,
            ],
            function ($user, $password) {
                $user->password = $password;
                $user->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new InvalidPayloadException('This password reset link is invalid or has expired.');
        }
    }
}
