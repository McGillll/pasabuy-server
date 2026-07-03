<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthLoginRequest;
use App\Http\Resources\UserResource;
use App\Interfaces\Auth\AuthServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuthServiceInterface $authService,
    ) {}

    public function login(AuthLoginRequest $request)
    {
        $result = $this->authService->login((object) $request->validated());

        return $this->success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Logged in successfully');
    }

    public function logout(Request $request)
    {
        $this->authService->logout((object) ['user' => $request->user()]);

        return $this->success(null, 'Logged out successfully');
    }

    public function me(Request $request)
    {
        $user = $this->authService->me((object) ['user' => $request->user()]);

        return $this->success(new UserResource($user), 'Authenticated user retrieved');
    }
}
