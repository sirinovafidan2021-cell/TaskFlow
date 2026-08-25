<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ManageApiTokensRequest;
use App\Http\Requests\Api\V1\StoreApiTokenRequest;
use App\Http\Resources\ApiTokenResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenController
{
    public function index(ManageApiTokensRequest $request): AnonymousResourceCollection
    {
        return ApiTokenResource::collection(
            $request->user()->tokens()->latest()->get(),
        );
    }

    public function store(StoreApiTokenRequest $request): JsonResponse
    {
        $data = $request->validated();
        $token = $request->user()->createToken($data['name'], $data['abilities']);

        return response()->json([
            'data' => [
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'abilities' => $data['abilities'],
            ],
        ], 201);
    }

    public function destroy(ManageApiTokensRequest $request, PersonalAccessToken $token): JsonResponse
    {
        abort_unless(
            $token->tokenable_type === $request->user()::class && $token->tokenable_id === $request->user()->id,
            404,
        );

        $token->delete();

        return response()->json(null, 204);
    }
}
