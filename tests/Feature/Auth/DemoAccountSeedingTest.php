<?php

use App\Models\User;

test('each role has a seeded demo login using the shared demo password', function (string $email, string $role) {
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
