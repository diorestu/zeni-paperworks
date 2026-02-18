<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
        });

        // Existing users become their own company roots so they keep access after rollout.
        DB::table('users')->whereNull('company_id')->update(['company_id' => DB::raw('id')]);

        $tables = ['clients', 'products', 'invoices', 'quotations', 'taxes', 'settings', 'bank_accounts'];
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
            });
        }

        // Default old records to the first available company.
        $defaultCompanyId = DB::table('users')->orderBy('id')->value('company_id') ?? 1;

        foreach (['clients', 'products', 'invoices', 'quotations', 'taxes', 'settings'] as $tableName) {
            DB::table($tableName)->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
        }

        // bank_accounts can be mapped exactly from owner user.
        DB::statement('UPDATE bank_accounts b JOIN users u ON u.id = b.user_id SET b.company_id = u.company_id WHERE b.company_id IS NULL');

        // Make settings key unique per company instead of globally.
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_key_unique');
            $table->unique(['company_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'key']);
            $table->unique('key');
        });

        foreach (['clients', 'products', 'invoices', 'quotations', 'taxes', 'settings', 'bank_accounts'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('company_id');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }
};
