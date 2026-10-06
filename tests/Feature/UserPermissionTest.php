<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an admin to access user management', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->getJson('/api/users');

    $response->assertOk();
});

it('prevents a regular user from accessing user management', function () {
    $user = User::factory()->create([
        'role' => 'user',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/users');

    $response->assertForbidden();
});

it('prevents a regular user from managing users', function () {
    $user = User::factory()->create([
        'role' => 'user',
    ]);

    $anotherUser = User::factory()->create([
        'role' => 'user',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->patchJson("/api/users/{$anotherUser->id}", [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => $anotherUser->email,
            'role' => 'user',
        ]);

    $response->assertForbidden();
});

it('allows an admin to update a user', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $user = User::factory()->create([
        'role' => 'user',
    ]);

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->patchJson("/api/users/{$user->id}", [
            'first_name' => 'Updated',
            'last_name' => 'User',
            'email' => $user->email,
            'account_type' => 'admin',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('role', 'admin');

    expect($user->refresh()->role)->toBe('admin');
});
