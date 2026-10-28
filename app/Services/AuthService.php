<?php

namespace App\Services;

use App\Exceptions\AuthenticationFailedException;
use App\Exceptions\InvalidPayloadException;
use App\Interfaces\Auth\AuthRepositoryInterface;
use App\Interfaces\Auth\AuthServiceInterface;
use App\Mail\EmailVerificationMail;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        protected AuthRepositoryInterface $authRepository,
    ) {}

    public function login(object $payload)
    {
        $user = $this->authRepository->findByEmail($payload->email);

        if (! $user) {
            throw new AuthenticationFailedException('No account found with this email address.', ['email' => ['No account found with this email address.']]);
        }

        if (! Hash::check($payload->password, $user->password)) {
            throw new AuthenticationFailedException('Incorrect password.', ['password' => ['Incorrect password.']]);
        }

        $expiresAt = ($payload->remember ?? false)
            ? Carbon::now()->addDays(30)
            : Carbon::now()->addHours(12);

        $token = $user->createToken('auth_token', $user->abilities(), $expiresAt)->plainTextToken;

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

    public function sendVerificationEmail(object $payload)
    {
        $user = $payload->user;

        if ($user->hasVerifiedEmail()) {
            return;
        }

        Mail::to($user->email)->queue(new EmailVerificationMail($user, $this->buildVerificationUrl($user)));
    }

    public function verifyEmail(object $payload)
    {
        $tokenRecord = $this->authRepository->findValidEmailVerificationToken(
            (object) ['token' => hash('sha256', $payload->token)]
        );

        if (! $tokenRecord) {
            throw new InvalidPayloadException('This verification link is invalid or has expired.');
        }

        $user = $tokenRecord->user;

        if (! $user->hasVerifiedEmail()) {
            $this->authRepository->markEmailAsVerified((object) ['user' => $user]);
        }

        $this->authRepository->deleteEmailVerificationTokens((object) ['user' => $user]);

        return $user;
    }

    protected function buildVerificationUrl(User $user)
    {
        $token = Str::random(64);

        $this->authRepository->createEmailVerificationToken((object) [
            'user' => $user,
            'token' => hash('sha256', $token),
            'expiresAt' => Carbon::now()->addMinutes(60),
        ]);

        return rtrim(config('app.frontend_url'), '/').'/verify-email?token='.$token;
    }
}
