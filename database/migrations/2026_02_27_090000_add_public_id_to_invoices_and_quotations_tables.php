<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('invoices', 'public_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('public_id', 26)->nullable()->after('id');
                $table->unique('public_id');
            });
        }

        if (!Schema::hasColumn('quotations', 'public_id')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->string('public_id', 26)->nullable()->after('id');
                $table->unique('public_id');
            });
        }

        DB::table('invoices')
            ->select('id')
            ->whereNull('public_id')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('invoices')
                        ->where('id', $row->id)
                        ->update(['public_id' => (string) Str::ulid()]);
                }
            });

        DB::table('quotations')
            ->select('id')
            ->whereNull('public_id')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('quotations')
                        ->where('id', $row->id)
                        ->update(['public_id' => (string) Str::ulid()]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'public_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropUnique('invoices_public_id_unique');
                $table->dropColumn('public_id');
            });
        }

        if (Schema::hasColumn('quotations', 'public_id')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->dropUnique('quotations_public_id_unique');
                $table->dropColumn('public_id');
            });
        }
    }
};
