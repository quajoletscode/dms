<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        match (DB::getDriverName()) {
            'mysql' => $this->createMysqlTriggers(),
            'sqlite' => $this->createSqliteTriggers(),
            default => null,
        };
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS stock_ledger_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS stock_ledger_no_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS journal_lines_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS journal_lines_no_delete');
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

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER journal_lines_no_update BEFORE UPDATE ON journal_lines
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'journal_lines is append-only: updates are not allowed';
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER journal_lines_no_delete BEFORE DELETE ON journal_lines
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'journal_lines is append-only: deletes are not allowed';
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

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER journal_lines_no_update BEFORE UPDATE ON journal_lines
            BEGIN
                SELECT RAISE(ABORT, 'journal_lines is append-only: updates are not allowed');
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER journal_lines_no_delete BEFORE DELETE ON journal_lines
            BEGIN
                SELECT RAISE(ABORT, 'journal_lines is append-only: deletes are not allowed');
            END
        SQL);
    }
};
