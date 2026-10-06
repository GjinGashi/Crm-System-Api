<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an authenticated user to create a client', function () {
    $user = User::factory()->admin()->create();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/clients', [
            'first_name' => 'Acme',
            'last_name' => 'Corporation',
            'email' => 'contact@acme.test',
            'phone' => '123456789',
            'company' => 'Acme Corporation',
            'address' => '123 Main Street',
            'city' => 'Pristina',
            'country' => 'Kosovo',
            'status' => 'Active',
            'notes' => 'Important client',
        ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('first_name', 'Acme')
        ->assertJsonPath('last_name', 'Corporation')
        ->assertJsonPath('email', 'contact@acme.test');

    $this->assertDatabaseHas('clients', [
        'first_name' => 'Acme',
        'last_name' => 'Corporation',
        'email' => 'contact@acme.test',
        'status' => 'Active',
    ]);
});

it('validates client data when creating a client', function () {
    $user = User::factory()->admin()->create();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/clients', [
            'first_name' => '',
            'last_name' => '',
            'email' => 'not-an-email',
            'status' => 'Invalid',
        ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'first_name',
            'last_name',
            'email',
            'status',
        ]);
});

it('allows an authenticated user to update a client', function () {
    $user = User::factory()->admin()->create();

    $client = Client::factory()->create([
        'first_name' => 'Old',
        'last_name' => 'Client',
        'email' => 'old@example.test',
        'status' => 'Active',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->putJson("/api/clients/{$client->id}", [
            'first_name' => 'Updated',
            'last_name' => 'Client',
            'email' => 'updated@example.test',
            'phone' => '987654321',
            'company' => 'Updated Company',
            'address' => '456 Updated Street',
            'city' => 'Pristina',
            'country' => 'Kosovo',
            'status' => 'Lead',
            'notes' => 'Updated notes',
        ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('first_name', 'Updated')
        ->assertJsonPath('last_name', 'Client')
        ->assertJsonPath('status', 'Lead');

    $this->assertDatabaseHas('clients', [
        'id' => $client->id,
        'first_name' => 'Updated',
        'last_name' => 'Client',
        'email' => 'updated@example.test',
        'status' => 'Lead',
    ]);
});

it('allows an authenticated user to delete a client without related projects', function () {
    $user = User::factory()->admin()->create();

    $client = Client::factory()->create();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->deleteJson("/api/clients/{$client->id}");

    $response
        ->assertOk()
        ->assertJson([
            'message' => 'Client deleted successfully',
        ]);

    $this->assertDatabaseMissing('clients', [
        'id' => $client->id,
    ]);
});

it('returns the client with its related projects', function () {
    $user = User::factory()->admin()->create();

    $client = Client::factory()->create();

    $project = Project::factory()->create([
        'client_id' => $client->id,
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson("/api/clients/{$client->id}");

    $response
        ->assertOk()
        ->assertJsonPath('id', $client->id)
        ->assertJsonPath('projects.0.id', $project->id);
});

it('can filter archived clients through the API', function () {
    $user = User::factory()->admin()->create();

    $activeClient = Client::factory()->create([
        'archived_at' => null,
        'status' => 'Active',
    ]);

    $archivedClient = Client::factory()->create([
        'archived_at' => now(),
        'status' => 'Archived',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/clients?archived=1');

    $response->assertOk();

    $ids = collect($response->json())->pluck('id');

    expect($ids)->toContain($archivedClient->id);
    expect($ids)->not->toContain($activeClient->id);
});
