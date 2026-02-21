<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->string('payment_provider')->nullable()->after('status');
            $table->string('payment_method')->nullable()->after('payment_provider');
            $table->string('external_order_id')->nullable()->unique()->after('payment_method');
            $table->string('external_transaction_id')->nullable()->after('external_order_id');
            $table->json('payment_payload')->nullable()->after('external_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->dropUnique('subscription_invoices_external_order_id_unique');
            $table->dropColumn([
                'payment_provider',
                'payment_method',
                'external_order_id',
                'external_transaction_id',
                'payment_payload',
            ]);
        });
    }
};
