<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('no')->unique();
            $table->foreignId('bank_account_id')->constrained();
            $table->enum('type', ['deposit', 'withdrawal', 'transfer_in', 'transfer_out']);
            $table->bigInteger('amount');
            // Only meaningful on a transfer_out row — a fee the bank charges
            // for the transfer, posted to Bank Charges alongside the moved amount.
            $table->bigInteger('charge')->default(0);
            // Unique where present; a nullable unique column allows any
            // number of NULLs (standard SQL semantics) — only an actual
            // duplicate slip reference collides.
            $table->string('slip_reference')->nullable()->unique();
            $table->foreignId('related_bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->boolean('reconciled')->default(false);
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transacted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
