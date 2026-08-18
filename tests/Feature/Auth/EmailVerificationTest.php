<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('sends verification notifications on registration and resend without real mail', function (): void {
    Notification::fake();
    $this->post(route('register.store'), ['name' => 'Verify Me', 'email' => 'verify@example.test', 'password' => 'password', 'password_confirmation' => 'password']);
    $user = User::query()->where('email', 'verify@example.test')->firstOrFail();
    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
    $this->actingAs($user)->post(route('verification.send'))->assertSessionHas('status');
    Notification::assertSentToTimes($user, VerifyEmail::class, 2);
});

it('verifies a signed URL and blocks unverified users from verified pages', function (): void {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), ['id' => $user->id, 'hash' => sha1($user->email)]);
    $this->actingAs($user)->get($url)->assertRedirect(route('home'));
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->actingAs($user)->get(route('verification.notice'))->assertRedirect(route('home'));
});

it('rejects an invalid verification URL', function (): void {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->get("/email/verify/{$user->id}/invalid")->assertForbidden();
});
