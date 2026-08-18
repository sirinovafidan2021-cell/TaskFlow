<?php

use App\Models\User;

it('authenticates a real bearer token and rejects missing or invalid tokens', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('pest')->plainTextToken;
    $this->withToken($token)->getJson('/api/v1/user')->assertOk()->assertJsonPath('data.id', $user->id);
    $this->flushHeaders();
    $this->app['auth']->forgetGuards();
    $this->getJson('/api/v1/user')->assertUnauthorized();
    $this->withToken('not-a-real-token')->getJson('/api/v1/user')->assertUnauthorized();
});

it('revokes a token through logout so it cannot access protected routes', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('pest');
    $this->withToken($token->plainTextToken)->postJson('/api/v1/logout')->assertOk();
    $this->flushHeaders();
    $this->app['auth']->forgetGuards();
    $this->withToken($token->plainTextToken)->getJson('/api/v1/user')->assertUnauthorized();
});
