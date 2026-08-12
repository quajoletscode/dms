<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loadin_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loadin_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('qty_good', 15, 3)->default(0);
            $table->decimal('qty_damaged', 15, 3)->default(0);
            $table->bigInteger('unit_cost')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loadin_request_items');
    }
};
