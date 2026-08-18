<?php

use App\Enums\UserRole;

it('renders the activity page only for users with activity permission', function (): void {
    $this->get('/activity')->assertRedirect('/login');
    $manager = userWithRole(UserRole::ProjectManager);
    $this->actingAs($manager)->get('/activity')->assertOk()->assertViewIs('activity::index');
    $member = userWithRole(UserRole::Member);
    $this->actingAs($member)->get('/activity')->assertForbidden();
});
