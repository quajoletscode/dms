<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loadout_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loadout_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('qty_requested', 15, 3);
            // Editable at approval (ApproveLoadout); qty_loaded/qty_received
            // are captured later and may legitimately differ (discrepancy).
            $table->decimal('qty_approved', 15, 3)->nullable();
            $table->decimal('qty_loaded', 15, 3)->nullable();
            $table->decimal('qty_received', 15, 3)->nullable();
            $table->bigInteger('unit_cost')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loadout_request_items');
    }
};
