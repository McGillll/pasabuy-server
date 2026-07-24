<?php

namespace App\Repositories;

use App\Interfaces\Auth\AuthRepositoryInterface;
use App\Models\EmailVerificationToken;
use App\Models\User;
use App\Traits\InteractsWithCache;
use Illuminate\Support\Carbon;

class AuthRepository implements AuthRepositoryInterface
{
    use InteractsWithCache;


    public function findByEmail(string $email)
    {
        return User::where('email', $email)->first();
    }

    public function createEmailVerificationToken(object $payload)
    {
        $token = new EmailVerificationToken;
        $token->user_id = $payload->user->id;
        $token->token = $payload->token;
        $token->expires_at = $payload->expiresAt;
        $token->save();

        return $token;
    }

    public function findValidEmailVerificationToken(object $payload)
    {
        return EmailVerificationToken::with('user')
            ->where('token', $payload->token)
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    public function deleteEmailVerificationTokens(object $payload)
    {
        EmailVerificationToken::where('user_id', $payload->user->id)->delete();
    }

    public function updateProfile(object $payload)
    {
        $user = $payload->user;
        $user->first_name = $payload->first_name;
        $user->last_name = $payload->last_name;
        $user->email = $payload->email;
        $user->save();

        if ($user->role === 'customer') {
            $this->bumpCacheVersion('customers');
        }

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
