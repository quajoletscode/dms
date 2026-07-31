<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->enum('location_type', ['warehouse', 'van']);
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('product_id');
            // 0 = not batch-tracked, so the unique key below stays meaningful:
            // a nullable batch_id would let MySQL/SQLite treat repeated NULLs as distinct rows.
            $table->unsignedBigInteger('batch_id')->default(0);
            $table->decimal('qty_on_hand', 15, 3)->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->unique(
                ['location_type', 'location_id', 'product_id', 'batch_id'],
                'stock_balances_location_product_batch_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};
