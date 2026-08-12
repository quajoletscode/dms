<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a third stock location type, 'damages' — a per-warehouse virtual
 * location AcceptLoadin moves damaged van stock into (LI-02), alongside the
 * existing 'warehouse'/'van' locations. On SQLite this rebuilds both tables
 * (no native ALTER of a CHECK/enum constraint), which — per decisions.md
 * ADR-15 — silently drops stock_ledger's append-only triggers; they are
 * re-created in the following migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        match (DB::getDriverName()) {
            'mysql' => $this->alterMysql(['warehouse', 'van', 'damages']),
            default => $this->alterGeneric(['warehouse', 'van', 'damages']),
        };
    }

    public function down(): void
    {
        match (DB::getDriverName()) {
            'mysql' => $this->alterMysql(['warehouse', 'van']),
            default => $this->alterGeneric(['warehouse', 'van']),
        };
    }

    /**
     * @param  list<string>  $values
     */
    private function alterMysql(array $values): void
    {
        $list = "'".implode("', '", $values)."'";
        DB::statement("ALTER TABLE stock_ledger MODIFY location_type ENUM({$list}) NOT NULL");
        DB::statement("ALTER TABLE stock_balances MODIFY location_type ENUM({$list}) NOT NULL");
    }

    /**
     * @param  list<string>  $values
     */
    private function alterGeneric(array $values): void
    {
        Schema::table('stock_ledger', function (Blueprint $table) use ($values) {
            $table->enum('location_type', $values)->change();
        });

        Schema::table('stock_balances', function (Blueprint $table) use ($values) {
            $table->enum('location_type', $values)->change();
        });
    }
};
