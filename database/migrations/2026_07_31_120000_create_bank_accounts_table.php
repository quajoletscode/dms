<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('account_no');
            $table->string('bank_name');
            // Each bank account gets its own dedicated GL account (unlike the
            // single shared Cash on Hand account) — RegisterBankAccount
            // creates both in one transaction.
            $table->foreignId('coa_account_id')->unique()->constrained('chart_of_accounts');
            $table->bigInteger('opening_balance')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
