<?php

use App\Enums\UserRole;
use App\Models\User;

it('renders the dashboard page only for authorized authenticated users', function (): void {
    $this->get('/dashboard')->assertRedirect('/login');
    $manager = userWithRole(UserRole::ProjectManager);
    $this->actingAs($manager)->get('/dashboard')->assertOk()->assertViewIs('dashboard::index');
    $user = User::factory()->create();
    $this->actingAs($user)->get('/dashboard')->assertForbidden();
});
