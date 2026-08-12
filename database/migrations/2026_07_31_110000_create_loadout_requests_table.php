<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loadout_requests', function (Blueprint $table) {
            $table->id();
            $table->string('no')->unique();
            $table->foreignId('van_storage_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            // LO-03: stock only moves warehouse -> van at the Received
            // confirmation; 'loaded' is a status-only checkpoint, not a
            // separate ledger location.
            $table->enum('status', ['requested', 'approved', 'loaded', 'received', 'rejected'])->default('requested');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('loaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('loaded_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loadout_requests');
    }
};
