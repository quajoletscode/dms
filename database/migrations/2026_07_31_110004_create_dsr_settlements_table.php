<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dsr_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('no')->unique();
            $table->foreignId('van_storage_id')->constrained();
            $table->foreignId('dsr_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('settlement_date');
            // Stock-value identity: opening + loadout - sales - loadin = closing.
            $table->bigInteger('opening_value')->default(0);
            $table->bigInteger('loadout_value')->default(0);
            $table->bigInteger('sales_value')->default(0);
            $table->bigInteger('loadin_value')->default(0);
            $table->bigInteger('closing_value')->default(0);
            // Cash identity: cash_expected = cash sales (+ collections, Phase 6).
            $table->bigInteger('cash_expected')->default(0);
            $table->bigInteger('cash_counted')->nullable();
            $table->bigInteger('cash_variance')->nullable();
            $table->enum('status', ['generated', 'signed_off'])->default('generated');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('signed_off_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signed_off_at')->nullable();
            $table->timestamps();

            $table->unique(['van_storage_id', 'settlement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dsr_settlements');
    }
};
