<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_sku_unique');
            $table->unique(['company_id', 'sku']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_invoice_number_unique');
            $table->unique(['company_id', 'invoice_number']);
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropUnique('quotations_quotation_number_unique');
            $table->unique(['company_id', 'quotation_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'quotation_number']);
            $table->unique('quotation_number');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'invoice_number']);
            $table->unique('invoice_number');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'sku']);
            $table->unique('sku');
        });
    }
};
