<?php

use App\Modules\Sales\Actions\CloseTillSession;
use App\Modules\Sales\Actions\OpenTillSession;
use App\Modules\Sales\Domain\Exceptions\TillSessionAlreadyOpenException;
use App\Support\Exceptions\IllegalTransitionException;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ChartOfAccountSeeder::class);
});

test('a till session opens with the given opening float', function () {
    $warehouse = createWarehouseFixture();
    actingAsUser(createCashierUser());

    $till = app(OpenTillSession::class)->execute($warehouse->id, '100.00');

    expect($till->status)->toBe('open')
        ->and($till->opening_float->toMajor())->toBe('100.00')
        ->and($till->closed_at)->toBeNull();
});

test('a user cannot open a second till session while one is already open', function () {
    $warehouse = createWarehouseFixture();
    actingAsUser(createCashierUser());

    app(OpenTillSession::class)->execute($warehouse->id, '100.00');

    expect(fn () => app(OpenTillSession::class)->execute($warehouse->id, '50.00'))
        ->toThrow(TillSessionAlreadyOpenException::class);
});

test('closing a till session with no cash movements has zero variance and posts nothing', function () {
    $warehouse = createWarehouseFixture();
    actingAsUser(createCashierUser());
    $till = app(OpenTillSession::class)->execute($warehouse->id, '100.00');

    $closed = app(CloseTillSession::class)->execute($till, '100.00');

    expect($closed->status)->toBe('closed')
        ->and($closed->expected_float->toMajor())->toBe('100.00')
        ->and($closed->variance->toMajor())->toBe('0.00');

    $this->assertDatabaseCount('journal_entries', 0);
});

test('closing an already-closed till session is blocked', function () {
    $warehouse = createWarehouseFixture();
    actingAsUser(createCashierUser());
    $till = app(OpenTillSession::class)->execute($warehouse->id, '100.00');
    $closed = app(CloseTillSession::class)->execute($till, '100.00');

    expect(fn () => app(CloseTillSession::class)->execute($closed, '100.00'))
        ->toThrow(IllegalTransitionException::class);
});
