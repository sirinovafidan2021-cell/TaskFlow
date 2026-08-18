<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

test('guests can view the registration page', function (): void {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Create your account');
});

test('authenticated users cannot view the registration page', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('register'))
        ->assertRedirect(route('home'));
});

test('a guest can register and is authenticated with the member role when it exists', function (): void {
    Role::findOrCreate(UserRole::Member->value, 'web');

    $response = $this->post(route('register.store'), [
        'name' => 'New Member',
        'email' => 'new.member@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => UserRole::Admin->value,
    ]);

    $response->assertRedirect(route('verification.notice'));
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', ['email' => 'new.member@example.test']);

    $user = User::query()->where('email', 'new.member@example.test')->firstOrFail();

    expect(Hash::check('password', $user->password))->toBeTrue()
        ->and($user->password)->not->toBe('password')
        ->and($user->hasRole(UserRole::Member->value))->toBeTrue()
        ->and($user->hasRole(UserRole::Admin->value))->toBeFalse();
});

test('registration rejects duplicate email, invalid data, and missing password confirmation', function (): void {
    User::factory()->create(['email' => 'taken@example.test']);

    $this->from(route('register'))
        ->post(route('register.store'), [
            'name' => '',
            'email' => 'taken@example.test',
            'password' => 'password',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors(['name', 'email', 'password']);
});

test('the existing login flow remains available', function (): void {
    $user = User::factory()->create([
        'email' => 'existing.user@example.test',
        'password' => Hash::make('password'),
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});
