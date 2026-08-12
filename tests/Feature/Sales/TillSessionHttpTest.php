<?php

use App\Modules\Sales\Models\TillCashMovement;
use App\Modules\Sales\Models\TillSession;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('a till session can be opened via http', function () {
    $warehouse = createWarehouseFixture();
    $cashier = createCashierUser();

    $this->actingAs($cashier)->post('/till-sessions', [
        'warehouse_id' => $warehouse->id,
        'opening_float' => '100.00',
    ])->assertRedirect();

    $session = TillSession::query()->firstOrFail();

    expect($session->status)->toBe('open')
        ->and($session->opening_float->toMajor())->toBe('100.00')
        ->and($session->user_id)->toBe($cashier->id);
});

test('opening a second till session redirects with a graceful error, not a crash', function () {
    $warehouse = createWarehouseFixture();
    $cashier = createCashierUser();

    $this->actingAs($cashier);
    openTillSessionFixture($warehouse->id);

    $this->actingAs($cashier)->post('/till-sessions', [
        'warehouse_id' => $warehouse->id,
        'opening_float' => '50.00',
    ])->assertSessionHasErrors('warehouse_id');

    expect(TillSession::query()->count())->toBe(1);
});

test('a till session can be closed via http', function () {
    $warehouse = createWarehouseFixture();
    $cashier = createCashierUser();

    $this->actingAs($cashier);
    $session = openTillSessionFixture($warehouse->id);

    $this->actingAs($cashier)->post("/till-sessions/{$session->id}/close", [
        'counted_float' => '100.00',
    ])->assertRedirect();

    expect($session->refresh()->status)->toBe('closed');
});

test('closing an already-closed till session surfaces a graceful error, not a crash', function () {
    $warehouse = createWarehouseFixture();
    $cashier = createCashierUser();

    $this->actingAs($cashier);
    $session = openTillSessionFixture($warehouse->id);

    $this->actingAs($cashier)->post("/till-sessions/{$session->id}/close", ['counted_float' => '100.00'])->assertRedirect();

    $response = $this->actingAs($cashier)->post("/till-sessions/{$session->id}/close", ['counted_float' => '100.00']);

    $response->assertRedirect();
    expect($response->baseResponse->getStatusCode())->not->toBe(500);
});

test('cash can be recorded in and out of an open till session via http', function () {
    $warehouse = createWarehouseFixture();
    $cashier = createCashierUser();

    $this->actingAs($cashier);
    $session = openTillSessionFixture($warehouse->id);

    $this->actingAs($cashier)->post("/till-sessions/{$session->id}/cash", [
        'type' => 'in',
        'amount' => '20.00',
        'reason' => 'Change fund top-up',
    ])->assertRedirect();

    expect(TillCashMovement::query()->where('till_session_id', $session->id)->where('type', 'in')->count())->toBe(1);
});

test('an accountant cannot open a till session', function () {
    $warehouse = createWarehouseFixture();
    $accountant = createAccountantUser();

    $this->actingAs($accountant)->post('/till-sessions', [
        'warehouse_id' => $warehouse->id,
        'opening_float' => '100.00',
    ])->assertForbidden();
});
