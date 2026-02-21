<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('is_down_payment')->default(false)->after('bank_account_id');
            $table->foreignId('parent_invoice_id')->nullable()->after('is_down_payment')->constrained('invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_invoice_id');
            $table->dropColumn('is_down_payment');
        });
    }
};

