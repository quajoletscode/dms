<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('scope_type');
            // 0 = no specific scope (company-wide), since a nullable column would let
            // MySQL/SQLite unique indexes treat multiple NULLs as distinct rows.
            $table->unsignedBigInteger('scope_id')->default(0);
            $table->string('document_type');
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedInteger('next_number')->default(1);
            $table->timestamps();

            $table->unique(['scope_type', 'scope_id', 'document_type', 'fiscal_year'], 'doc_number_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_number_sequences');
    }
};
