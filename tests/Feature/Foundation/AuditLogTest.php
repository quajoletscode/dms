<?php

use App\Models\AuditLog;
use App\Modules\Finance\Models\ChartOfAccount;

test('creating an audited model writes a create audit log entry', function () {
    $account = ChartOfAccount::query()->create([
        'code' => '1000',
        'name' => 'Inventory',
        'type' => 'asset',
    ]);

    $log = AuditLog::query()
        ->where('auditable_type', $account->getMorphClass())
        ->where('auditable_id', $account->id)
        ->where('action', 'create')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->after['code'])->toBe('1000')
        ->and($log->before)->toBeNull();
});

test('updating an audited model writes an update audit log entry with only the changed fields', function () {
    $account = ChartOfAccount::query()->create([
        'code' => '1000',
        'name' => 'Inventory',
        'type' => 'asset',
    ]);

    $account->update(['name' => 'Inventory - Renamed']);

    $log = AuditLog::query()
        ->where('auditable_type', $account->getMorphClass())
        ->where('auditable_id', $account->id)
        ->where('action', 'update')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->before)->toBe(['name' => 'Inventory'])
        ->and($log->after)->toBe(['name' => 'Inventory - Renamed']);
});

test('deleting an audited model writes a delete audit log entry', function () {
    $account = ChartOfAccount::query()->create([
        'code' => '1000',
        'name' => 'Inventory',
        'type' => 'asset',
    ]);

    $account->delete();

    $log = AuditLog::query()
        ->where('auditable_type', $account->getMorphClass())
        ->where('auditable_id', $account->id)
        ->where('action', 'delete')
        ->first();

    expect($log)->not->toBeNull();
});
