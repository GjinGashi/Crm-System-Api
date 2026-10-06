<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an authenticated user to create a project', function () {
    $user = User::factory()->admin()->create();
    $client = Client::factory()->create();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/projects', [
            'client_id' => $client->id,
            'name' => 'New CRM Project',
            'description' => 'Project for testing.',
            'status' => 'Planning',
            'priority' => 'Medium',
            'start_date' => '2026-09-01',
            'due_date' => '2026-09-20',
            'budget' => 5000,
        ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('name', 'New CRM Project')
        ->assertJsonPath('client_id', $client->id);

    $this->assertDatabaseHas('projects', [
        'name' => 'New CRM Project',
        'client_id' => $client->id,
        'status' => 'Planning',
        'priority' => 'Medium',
    ]);
});

it('validates project data when creating a project', function () {
    $user = User::factory()->admin()->create();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/projects', [
            'client_id' => 999999,
            'name' => '',
            'status' => 'Invalid',
            'priority' => 'Invalid',
            'start_date' => '2026-09-20',
            'due_date' => '2026-09-10',
            'budget' => -50,
        ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'client_id',
            'name',
            'status',
            'priority',
            'due_date',
            'budget',
        ]);
});

it('rejects a project due date before its start date', function () {
    $user = User::factory()->admin()->create();
    $client = Client::factory()->create();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/projects', [
            'client_id' => $client->id,
            'name' => 'Invalid Dates Project',
            'status' => 'Planning',
            'priority' => 'Low',
            'start_date' => '2026-09-20',
            'due_date' => '2026-09-10',
        ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('due_date');
});

it('allows an authenticated user to update a project', function () {
    $user = User::factory()->admin()->create();
    $client = Client::factory()->create();
    $project = Project::factory()->create([
        'client_id' => $client->id,
        'name' => 'Old Project Name',
        'status' => 'Planning',
        'priority' => 'Low',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->putJson("/api/projects/{$project->id}", [
            'client_id' => $client->id,
            'name' => 'Updated Project Name',
            'description' => 'Updated description.',
            'status' => 'In Progress',
            'priority' => 'High',
            'start_date' => '2026-09-05',
            'due_date' => '2026-09-25',
            'budget' => 7500,
        ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('name', 'Updated Project Name')
        ->assertJsonPath('status', 'In Progress')
        ->assertJsonPath('priority', 'High');

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'Updated Project Name',
        'status' => 'In Progress',
        'priority' => 'High',
    ]);
});

it('allows an authenticated user to archive and restore a project', function () {
    $user = User::factory()->admin()->create();
    $project = Project::factory()->create([
        'archived_at' => null,
    ]);

    $archiveResponse = $this
        ->actingAs($user, 'sanctum')
        ->patchJson("/api/projects/{$project->id}/archive");

    $archiveResponse
        ->assertOk()
        ->assertJson([
            'message' => 'Project archived successfully',
        ]);

    $project->refresh();

    expect($project->archived_at)->not->toBeNull();

    $restoreResponse = $this
        ->actingAs($user, 'sanctum')
        ->patchJson("/api/projects/{$project->id}/restore");

    $restoreResponse
        ->assertOk()
        ->assertJson([
            'message' => 'Project restored successfully',
        ]);

    $project->refresh();

    expect($project->archived_at)->toBeNull();
});

it('allows an authenticated user to delete a project', function () {
    $user = User::factory()->admin()->create();
    $project = Project::factory()->create();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->deleteJson("/api/projects/{$project->id}");

    $response
        ->assertOk()
        ->assertJson([
            'message' => 'Project deleted succesfully',
        ]);

    $this->assertDatabaseMissing('projects', [
        'id' => $project->id,
    ]);
});

it('returns a project with its client and related tasks', function () {
    $user = User::factory()->admin()->create();
    $client = Client::factory()->create();

    $project = Project::factory()->create([
        'client_id' => $client->id,
    ]);

    $task = Task::create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'title' => 'Project Task',
        'description' => 'Task belonging to project.',
        'status' => 'Todo',
        'priority' => 'Low',
        'start_time' => '2026-09-10 09:00:00',
        'end_time' => '2026-09-10 10:00:00',
        'due_date' => '2026-09-15',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson("/api/projects/{$project->id}");

    $response
        ->assertOk()
        ->assertJsonPath('client.id', $client->id)
        ->assertJsonPath('tasks.0.id', $task->id);
});

it('can filter archived projects through the API', function () {
    $user = User::factory()->admin()->create();

    $activeProject = Project::factory()->create([
        'archived_at' => null,
    ]);

    $archivedProject = Project::factory()->create([
        'archived_at' => now(),
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/projects?archived=1');

    $response->assertOk();

    $ids = collect($response->json())->pluck('id');

    expect($ids)->toContain($archivedProject->id);
    expect($ids)->not->toContain($activeProject->id);
});
