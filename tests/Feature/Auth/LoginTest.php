<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('logs a user in with valid web credentials', function (): void {
    $user = User::factory()->create(['password' => Hash::make('password')]);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
});

it('rejects invalid or missing web credentials', function (): void {
    $this->from(route('login'))->post(route('login.store'), [])->assertRedirect(route('login'))->assertSessionHasErrors(['email', 'password']);
    $user = User::factory()->create();
    $this->from(route('login'))->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertRedirect(route('login'))->assertSessionHasErrors('email');
});
