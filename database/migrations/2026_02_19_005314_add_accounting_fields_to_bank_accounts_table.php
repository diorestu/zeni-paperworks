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
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('accounting_account_id')->nullable()->after('is_default');
            $table->decimal('opening_balance', 15, 2)->default(0)->after('accounting_account_id');
            $table->foreign('accounting_account_id')->references('id')->on('accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropForeign(['accounting_account_id']);
            $table->dropColumn(['accounting_account_id', 'opening_balance']);
        });
    }
};
