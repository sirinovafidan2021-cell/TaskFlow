<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use App\Services\AuthTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\NewAccessToken;

class AuthController extends Controller
{
    public function __construct(private readonly AuthTokenService $authTokens) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        return $this->tokenResponse($this->authTokens->register($request->data()), 'Registration successful.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $request->ensureIsNotRateLimited();
        $token = $this->authTokens->login($request->data());

        if ($token === null) {
            $request->recordFailedAttempt();
        }

        $request->clearRateLimit();

        return $this->tokenResponse($token, 'Login successful.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authTokens->revokeCurrentToken($request->user());

        return response()->json(['success' => true, 'message' => 'Logout successful.']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Authenticated user retrieved successfully.',
            'data' => new UserResource($request->user()),
        ]);
    }

    public function verifiedUser(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Verified user retrieved successfully.',
            'data' => new UserResource($request->user()),
        ]);
    }

    private function tokenResponse(NewAccessToken $token, string $message, int $status = 200): JsonResponse
    {
        /** @var User $user */
        $user = $token->accessToken->tokenable;

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'user' => new UserResource($user),
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
            ],
        ], $status);
    }
}
