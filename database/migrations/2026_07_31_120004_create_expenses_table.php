<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('no')->unique();
            $table->foreignId('expense_category_id')->constrained();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            // Null means paid from Cash on Hand; set means paid from that bank account.
            $table->foreignId('paid_from_bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->bigInteger('amount');
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expensed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
