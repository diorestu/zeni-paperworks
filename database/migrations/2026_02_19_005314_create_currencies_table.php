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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('code', 3);                    // ISO 4217: IDR, USD, EUR
            $table->string('name');                       // Indonesian Rupiah, US Dollar
            $table->string('symbol', 5);                  // Rp, $, €
            $table->decimal('exchange_rate', 15, 6)->default(1); // rate to base currency
            $table->boolean('is_base')->default(false);   // one per company
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
