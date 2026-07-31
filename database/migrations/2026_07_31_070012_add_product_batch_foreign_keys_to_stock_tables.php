<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backfills the product_id/batch_id foreign keys deferred from Phase 0 (see
 * docs/decisions.md ADR-13) now that products/batches exist.
 *
 * stock_balances.batch_id is intentionally NOT constrained here: it defaults
 * to 0 for "no batch" (see ADR-7), which is never a real batches.id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_ledger', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->nullOnDelete();
        });

        Schema::table('stock_balances', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_ledger', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['batch_id']);
        });

        Schema::table('stock_balances', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
    }
};
