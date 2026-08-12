<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Same situation as 2026_07_31_070013: the previous migration widened
 * stock_ledger's location_type enum, which on SQLite rebuilds the table and
 * silently drops its append-only triggers. Re-create them, idempotently.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS stock_ledger_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS stock_ledger_no_delete');

        match (DB::getDriverName()) {
            'mysql' => $this->createMysqlTriggers(),
            'sqlite' => $this->createSqliteTriggers(),
            default => null,
        };
    }

    public function down(): void
    {
        // Nothing to reverse: down() of 2026_07_31_060508 already drops these.
    }

    private function createMysqlTriggers(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER stock_ledger_no_update BEFORE UPDATE ON stock_ledger
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'stock_ledger is append-only: updates are not allowed';
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER stock_ledger_no_delete BEFORE DELETE ON stock_ledger
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'stock_ledger is append-only: deletes are not allowed';
            END
        SQL);
    }

    private function createSqliteTriggers(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER stock_ledger_no_update BEFORE UPDATE ON stock_ledger
            BEGIN
                SELECT RAISE(ABORT, 'stock_ledger is append-only: updates are not allowed');
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER stock_ledger_no_delete BEFORE DELETE ON stock_ledger
            BEGIN
                SELECT RAISE(ABORT, 'stock_ledger is append-only: deletes are not allowed');
            END
        SQL);
    }
};
