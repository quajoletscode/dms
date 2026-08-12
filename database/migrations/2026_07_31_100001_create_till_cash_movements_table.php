<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('till_cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('till_session_id')->constrained();
            $table->enum('type', ['in', 'out']);
            $table->bigInteger('amount');
            $table->string('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('till_cash_movements');
    }
};
