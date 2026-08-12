<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('guests are redirected to login from a protected route', function () {
    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/warehouses')->assertRedirect('/login');
});

test('visiting the site root redirects a guest to login', function () {
    $this->get('/')->assertRedirect('/login');
});

test('visiting the site root redirects an authenticated user to the dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)->get('/')->assertRedirect('/dashboard');
});

test('the login page never exposes demo accounts outside the local environment', function () {
    $this->seed();

    $this->get('/login')->assertInertia(fn (Assert $page) => $page
        ->component('Auth/Login')
        ->where('demoAccounts', [])
    );
});

test('the login page lists seeded demo accounts in the local environment', function () {
    $this->seed();

    // Forcing the environment to 'local' here also defeats Laravel Boost's
    // "skip during unit tests" guard (it checks the same environment('local')
    // flag) and Laravel's usual CSRF bypass for the test environment, so
    // this test only exercises the GET — it doesn't chain a POST afterward
    // (see the data-driven login test below for that, run in the normal
    // test environment). Boost's dev-tooling also rewrites the response
    // HTML in a way AssertableInertia doesn't parse, so this asserts
    // directly against the raw body — the JSON payload itself is unaffected.
    $this->app->detectEnvironment(fn () => 'local');

    $content = $this->get('/login')->assertOk()->getContent();

    expect($content)->toContain('"demoAccounts":[{"email":"superadmin@example.com","label":"Super Admin","password":"Pass$12"}');
    expect($content)->toContain('"email":"warehouse.manager@example.com"')
        ->and($content)->toContain('"email":"dsr@example.com"')
        ->and($content)->toContain('"email":"accountant@example.com"')
        ->and($content)->toContain('"email":"cashier@example.com"');
});

test('demo accounts are hidden when the database has not been seeded, even in the local environment', function () {
    $this->app->detectEnvironment(fn () => 'local');

    $content = $this->get('/login')->assertOk()->getContent();

    expect($content)->toContain('"demoAccounts":[]');
});

test('each seeded demo account can sign in with the shared demo password', function (string $email, string $role) {
    $this->seed();

    $response = $this->post('/login', [
        'email' => $email,
        'password' => 'Pass$12',
    ]);

    $response->assertRedirect('/dashboard');

    $user = User::query()->where('email', $email)->firstOrFail();
    $this->assertAuthenticatedAs($user);
    expect($user->hasRole($role))->toBeTrue();
})->with([
    'super admin' => ['superadmin@example.com', 'super_admin'],
    'warehouse manager' => ['warehouse.manager@example.com', 'warehouse_manager'],
    'dsr' => ['dsr@example.com', 'dsr'],
    'accountant' => ['accountant@example.com', 'accountant'],
    'wholesale cashier' => ['cashier@example.com', 'wholesale_cashier'],
]);

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
