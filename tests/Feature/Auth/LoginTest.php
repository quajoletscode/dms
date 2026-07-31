<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('guests are redirected to login from a protected route', function () {
    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/warehouses')->assertRedirect('/login');
});

test('a user can log in with valid credentials and reach a protected route', function () {
    $user = User::factory()->create(['password' => bcrypt('correct-password')]);
    $user->assignRole('super_admin');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);

    $this->get('/warehouses')->assertOk();
});

test('login fails with an invalid password', function () {
    $user = User::factory()->create(['password' => bcrypt('correct-password')]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('a logged in user can log out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/logout')->assertRedirect('/login');

    $this->assertGuest();
});
