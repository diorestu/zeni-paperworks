<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pending_plan_name')->nullable()->after('plan_renews_at');
            $table->date('pending_plan_effective_at')->nullable()->after('pending_plan_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pending_plan_name', 'pending_plan_effective_at']);
        });
    }
};

