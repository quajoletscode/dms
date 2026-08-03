<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->decimal('qty', 15, 3);
            $table->bigInteger('unit_price');
            $table->bigInteger('discount')->default(0);
            // Tax rate is snapshotted at invoice time (SO-03/spec's "tax rate at
            // invoice date"), not copied from an earlier Sales Order/Proforma line.
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->bigInteger('tax')->default(0);
            // COGS snapshot: the product's cost_price at the moment of sale, so a
            // later cost_price change never retroactively changes this invoice's COGS.
            $table->bigInteger('unit_cost')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
