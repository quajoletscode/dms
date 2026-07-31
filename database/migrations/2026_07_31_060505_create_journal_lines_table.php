<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts');
            $table->bigInteger('debit')->default(0);
            $table->bigInteger('credit')->default(0);
            $table->nullableMorphs('partner');
            $table->string('memo')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // A line is either a debit or a credit, never both. Enforced at the DB level
        // on MySQL (the production target); SQLite (local/test) relies on the
        // PostingEngine/model-level guard instead since it can't ALTER TABLE ... ADD CHECK.
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE journal_lines ADD CONSTRAINT chk_journal_lines_debit_xor_credit '.
                'CHECK (NOT (debit > 0 AND credit > 0))'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
    }
};
