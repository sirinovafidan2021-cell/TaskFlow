<?php

use App\Models\User;

it('logs an authenticated web user out and invalidates the session', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest();
});
