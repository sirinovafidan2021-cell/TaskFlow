<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\CreatePersonalAccessTokenData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreatePersonalAccessTokenRequest;
use App\Http\Resources\AuthenticatedUserResource;
use App\Services\AuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticationController extends Controller
{
    public function store(
        CreatePersonalAccessTokenRequest $request,
        AuthenticationService $authentication,
    ): JsonResponse {
        $token = $authentication->createPersonalAccessToken(
            CreatePersonalAccessTokenData::fromValidated($request->validated()),
        );

        return response()->json([
            'data' => [
                'token' => $token->plainTextToken,
                'abilities' => $authentication->tokenAbilities(),
            ],
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request): AuthenticatedUserResource
    {
        return new AuthenticatedUserResource($request->user());
    }

    public function destroy(Request $request, AuthenticationService $authentication): Response
    {
        $authentication->revokeCurrentAccessToken($request->user());

        return response()->noContent();
    }
}
