<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('van_storages', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('vehicle_no')->nullable();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            // Nullable-unique: many vans may have no DSR assigned, but an assigned
            // DSR can drive exactly one van at a time (VAN-01, 9.2 assumption).
            $table->foreignId('dsr_user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('van_storages');
    }
};
