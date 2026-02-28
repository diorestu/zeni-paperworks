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
        Schema::create('package_configs', function (Blueprint $table) {
            $table->id();
            $table->string('plan_name')->unique();
            $table->unsignedInteger('monthly_price')->default(0);
            $table->unsignedInteger('yearly_price')->default(0);
            $table->unsignedInteger('invoice_limit')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_configs');
    }
};

