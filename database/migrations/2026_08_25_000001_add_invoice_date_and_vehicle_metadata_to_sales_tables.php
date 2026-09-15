<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('invoice_date')->nullable()->after('due_date');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->date('service_date')->nullable()->after('batch_id');
            $table->string('vehicle_no')->nullable()->after('service_date');
            $table->string('line_description')->nullable()->after('vehicle_no');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['service_date', 'vehicle_no', 'line_description']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('invoice_date');
        });
    }
};
