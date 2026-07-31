<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->enum('location_type', ['warehouse', 'van']);
            $table->unsignedBigInteger('location_id');
            // product_id/batch_id reference tables created in Phase 1 (MasterData);
            // the FK constraints are added there once those tables exist.
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->decimal('qty_in', 15, 3)->default(0);
            $table->decimal('qty_out', 15, 3)->default(0);
            $table->bigInteger('unit_cost')->default(0);
            $table->string('doc_type');
            $table->unsignedBigInteger('doc_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->timestamp('created_at')->nullable();

            $table->index(['location_type', 'location_id', 'product_id', 'batch_id'], 'stock_ledger_location_product_batch_idx');
            $table->index(['doc_type', 'doc_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledger');
    }
};
