<?php

use App\Enums\UserRole;
use Modules\Projects\Models\Project;

it('redirects guests and renders project pages for an authorized verified user', function (): void {
    $this->get('/projects')->assertRedirect('/login');

    $manager = userWithRole(UserRole::ProjectManager);
    $project = Project::factory()->create(['owner_id' => $manager->id]);
    $this->actingAs($manager)->get('/projects')->assertOk()->assertViewIs('projects::index');
    $this->actingAs($manager)->get("/projects/{$project->id}")->assertOk()->assertViewIs('projects::show');
    $this->actingAs($manager)->post('/projects', ['name' => 'Web project', 'description' => 'Created from web'])->assertRedirect();
    $this->assertDatabaseHas('projects', ['name' => 'Web project', 'owner_id' => $manager->id]);
});
