<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_journals', function (Blueprint $table) {
            $table->id();
            $table->string('no')->unique();
            $table->string('memo')->nullable();
            $table->bigInteger('total_debit');
            $table->bigInteger('total_credit');
            // Below the configured threshold: posts immediately (skips
            // pending_approval). Above it: held until a different user
            // approves — see ApproveManualJournal.
            $table->enum('status', ['posted', 'pending_approval', 'rejected'])->default('pending_approval');
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_journals');
    }
};
