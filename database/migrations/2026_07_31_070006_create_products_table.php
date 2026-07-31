<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('unit_id')->constrained('units');
            $table->bigInteger('cost_price')->default(0);
            $table->bigInteger('wholesale_price')->default(0);
            $table->bigInteger('retail_price')->default(0);
            $table->bigInteger('van_price')->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('reorder_level', 15, 3)->default(0);
            $table->boolean('track_expiry')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
