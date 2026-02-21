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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('parent_id')->nullable()->index();  // hierarchical
            $table->string('code', 20)->index();         // e.g., "1000", "1100"
            $table->string('name');                       // e.g., "Cash", "Accounts Receivable"
            $table->enum('type', [
                'asset', 'liability', 'equity', 'revenue', 'expense'
            ]);
            $table->enum('subtype', [
                // Assets
                'current_asset', 'fixed_asset', 'other_asset',
                // Liabilities
                'current_liability', 'long_term_liability',
                // Equity
                'owner_equity', 'retained_earnings',
                // Revenue
                'operating_revenue', 'other_revenue',
                // Expenses
                'operating_expense', 'cost_of_goods', 'other_expense',
            ]);
            $table->text('description')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->boolean('is_system')->default(false);   // prevent deletion of system accounts
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->foreign('parent_id')->references('id')->on('accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
