<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_journal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts');
            $table->bigInteger('debit')->default(0);
            $table->bigInteger('credit')->default(0);
            $table->nullableMorphs('partner');
            $table->string('memo')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_journal_lines');
    }
};
