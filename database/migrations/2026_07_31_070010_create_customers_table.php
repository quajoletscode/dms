<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('type', ['wholesale', 'retailer'])->default('wholesale');
            $table->bigInteger('credit_limit')->default(0);
            $table->string('price_category')->nullable();
            $table->foreignId('coa_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->string('rims_tenant_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
