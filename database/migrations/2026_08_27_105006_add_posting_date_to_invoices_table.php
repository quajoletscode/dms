<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // BC's "Posting Date": the date the invoice's journal entry lands
            // on, distinct from invoice_date ("Document Date") — previously
            // PostingEngine::post() always posted as of "today".
            $table->date('posting_date')->nullable()->after('invoice_date');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('posting_date');
        });
    }
};
