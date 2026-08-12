<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('no')->unique();
            $table->foreignId('van_storage_id')->constrained();
            // Captured at collection time from the van's own warehouse_id —
            // a plain column, not resolved later via a WarehouseScope'd
            // relation (decisions.md ADR-28's lesson).
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('dsr_user_id')->constrained('users');
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('invoice_id')->constrained();
            $table->bigInteger('amount');
            // BNK-06: with_dsr -> handed_over -> deposited, traced to a
            // person at every hop (dsr_user_id / handed_over_by / the
            // depositing bank_transaction's created_by).
            $table->enum('status', ['with_dsr', 'handed_over', 'deposited'])->default('with_dsr');
            $table->timestamp('collected_at');
            $table->foreignId('handed_over_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handed_over_at')->nullable();
            $table->foreignId('bank_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('deposited_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
