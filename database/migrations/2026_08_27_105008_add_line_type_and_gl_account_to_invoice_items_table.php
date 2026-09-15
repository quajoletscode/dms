<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            // BC's line "Type" column: item (existing behavior), gl_account
            // (a non-stock charge posted straight to a ledger account), or
            // comment (descriptive text, no amount).
            $table->enum('line_type', ['item', 'gl_account', 'comment'])->default('item')->after('invoice_id');
            $table->foreignId('gl_account_id')->nullable()->after('product_id')->constrained('chart_of_accounts')->nullOnDelete();
        });

        // A gl_account/comment line has no product — drop the NOT NULL FK and
        // re-add it without the constraint that assumed every line was stock.
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products');
            $table->dropForeign(['gl_account_id']);
            $table->dropColumn(['line_type', 'gl_account_id']);
        });
    }
};
