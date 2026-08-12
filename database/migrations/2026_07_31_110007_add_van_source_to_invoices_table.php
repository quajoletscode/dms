<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the 'van' Invoice source (DSR field sales, RecordVanSale) and a
 * nullable van_storage_id link so GenerateDsrSettlement can query a van's
 * sales for a given day. invoices has no triggers, so unlike the stock
 * tables this needs no trigger-recreation follow-up on SQLite.
 */
return new class extends Migration
{
    public function up(): void
    {
        match (DB::getDriverName()) {
            'mysql' => DB::statement("ALTER TABLE invoices MODIFY source ENUM('sales_order', 'proforma', 'pos', 'van') NOT NULL DEFAULT 'sales_order'"),
            default => Schema::table('invoices', function (Blueprint $table) {
                $table->enum('source', ['sales_order', 'proforma', 'pos', 'van'])->default('sales_order')->change();
            }),
        };

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('van_storage_id')->nullable()->after('warehouse_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('van_storage_id');
        });

        match (DB::getDriverName()) {
            'mysql' => DB::statement("ALTER TABLE invoices MODIFY source ENUM('sales_order', 'proforma', 'pos') NOT NULL DEFAULT 'sales_order'"),
            default => Schema::table('invoices', function (Blueprint $table) {
                $table->enum('source', ['sales_order', 'proforma', 'pos'])->default('sales_order')->change();
            }),
        };
    }
};
