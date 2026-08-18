<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;

test('an API user can register and receives a Sanctum bearer token', function (): void {
    Notification::fake();

    $response = $this->postJson('/api/v1/register', [
        'name' => 'API Member',
        'email' => 'api.member@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'device_name' => 'Pest',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.email', 'api.member@example.test')
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonStructure(['data' => ['token']]);

    $this->assertDatabaseHas('personal_access_tokens', ['tokenable_type' => User::class]);
});

test('unverified token users are denied verified-only API endpoints', function (): void {
    $user = User::factory()->unverified()->create();
    $token = $user->createToken('Pest')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/verified-user')
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

test('logout revokes the bearer token used for the request', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('Pest');

    $this->withToken($token->plainTextToken)
        ->postJson('/api/v1/logout')
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
});
