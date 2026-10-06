<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an authenticated user to create a task', function () {
    $user = User::factory()->admin()->create();
    $project = Project::factory()->create();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/tasks', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'title' => 'New CRM Task',
            'description' => 'Task for testing.',
            'status' => 'Todo',
            'priority' => 'Medium',
            'start_time' => '2026-09-10 09:00:00',
            'end_time' => '2026-09-10 10:00:00',
            'due_date' => '2026-09-15',
        ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('title', 'New CRM Task')
        ->assertJsonPath('project_id', $project->id)
        ->assertJsonPath('user_id', $user->id);

    $this->assertDatabaseHas('tasks', [
        'title' => 'New CRM Task',
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'Todo',
        'priority' => 'Medium',
    ]);
});

it('validates task data when creating a task', function () {
    $user = User::factory()->admin()->create();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/tasks', [
            'project_id' => 999999,
            'user_id' => 999999,
            'title' => 'ab',
            'status' => 'Invalid',
            'priority' => 'Invalid',
        ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'project_id',
            'user_id',
            'title',
            'status',
            'priority',
        ]);
});

it('allows an authenticated user to update a task', function () {
    $user = User::factory()->admin()->create();
    $project = Project::factory()->create();

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'title' => 'Old Task',
        'status' => 'Todo',
        'priority' => 'Low',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->putJson("/api/tasks/{$task->id}", [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'title' => 'Updated Task',
            'description' => 'Updated description.',
            'status' => 'In Progress',
            'priority' => 'High',
            'start_time' => '2026-09-10 09:00:00',
            'end_time' => '2026-09-10 11:00:00',
            'due_date' => '2026-09-20',
        ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('title', 'Updated Task')
        ->assertJsonPath('status', 'In Progress')
        ->assertJsonPath('priority', 'High');

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Updated Task',
        'status' => 'In Progress',
        'priority' => 'High',
    ]);
});

it('allows an authenticated user to delete a task', function () {
    $user = User::factory()->admin()->create();
    $project = Project::factory()->create();

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->deleteJson("/api/tasks/{$task->id}");

    $response
        ->assertOk()
        ->assertJson([
            'message' => 'Task Deleted',
        ]);

    $this->assertDatabaseMissing('tasks', [
        'id' => $task->id,
    ]);
});

it('returns a task with its project and assigned user', function () {
    $user = User::factory()->admin()->create();
    $project = Project::factory()->create();

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson("/api/tasks/{$task->id}");

    $response
        ->assertOk()
        ->assertJsonPath('project.id', $project->id)
        ->assertJsonPath('user.id', $user->id);
});
