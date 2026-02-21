# 🧾 Accounting Module — Implementation Plan

> **Module Type:** SaaS Addon (subscription-gated)
> **Stack:** Laravel 12 + Inertia.js + Vue 3 + TailwindCSS 4
> **Multi-tenancy:** `company_id` scoping via `BelongsToCompany` trait
> **Created:** 2026-02-18

---

## 📋 Table of Contents

1. [Overview & Architecture](#1-overview--architecture)
2. [Module Activation & Subscription Gating](#2-module-activation--subscription-gating)
3. [Database Migrations](#3-database-migrations)
4. [Models & Relationships](#4-models--relationships)
5. [Controllers](#5-controllers)
6. [Routes](#6-routes)
7. [Service Classes](#7-service-classes)
8. [Middleware](#8-middleware)
9. [Vue Pages & Components](#9-vue-pages--components)
10. [Implementation Phases](#10-implementation-phases)

---

## 1. Overview & Architecture

### What Exists Today
Your app (Zeni Paperwork) is an **invoicing & quotation** platform with:
- **Models:** `User`, `Client`, `Product`, `Invoice`, `InvoiceItem`, `Quotation`, `QuotationItem`, `Tax`, `BankAccount`, `Setting`, `SubscriptionInvoice`
- **Multi-tenancy:** `company_id` + `BelongsToCompany` global scope trait
- **Subscriptions:** `plan_name` + `plan_renews_at` on the `users` table
- **Roles:** `super_admin`, `admin`, `user`

### What the Accounting Module Adds
A full **double-entry accounting system** that integrates with existing invoices, adding:

| Feature Area | Description |
|---|---|
| **Chart of Accounts** | Hierarchical account structure (Assets, Liabilities, Equity, Revenue, Expenses) |
| **Journal Entries** | Double-entry journal entries with debit/credit lines |
| **General Ledger** | Ledger view per account with running balances |
| **Accounts Receivable** | Auto-tracked from invoices; payment recording |
| **Accounts Payable** | Bill/expense management and payments |
| **Expense Tracking** | Categorized expenses with receipt uploads |
| **Payment Recording** | Payments linked to invoices/bills |
| **Bank Reconciliation** | Match transactions to bank statements |
| **Financial Reports** | Profit & Loss, Balance Sheet, Trial Balance, Cash Flow |
| **Fiscal Periods** | Period management with lock/close capability |

### Architecture Decisions

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Accounting/           ← All accounting controllers namespaced
│   │       ├── ChartOfAccountController.php
│   │       ├── JournalEntryController.php
│   │       ├── ExpenseController.php
│   │       ├── PaymentController.php
│   │       ├── BillController.php
│   │       ├── BankReconciliationController.php
│   │       ├── ReportController.php
│   │       └── FiscalPeriodController.php
│   └── Middleware/
│       └── EnsureAccountingModule.php   ← Subscription gate
├── Models/
│   └── Accounting/               ← All accounting models namespaced
│       ├── Account.php
│       ├── JournalEntry.php
│       ├── JournalEntryLine.php
│       ├── Expense.php
│       ├── ExpenseCategory.php
│       ├── Payment.php
│       ├── Bill.php
│       ├── BillItem.php
│       ├── BankTransaction.php
│       ├── BankReconciliation.php
│       └── FiscalPeriod.php
├── Services/
│   └── Accounting/
│       ├── JournalService.php
│       ├── LedgerService.php
│       ├── ReportService.php
│       └── ReconciliationService.php
resources/js/
├── Pages/
│   └── Accounting/               ← All accounting Vue pages
│       ├── ChartOfAccounts/
│       ├── JournalEntries/
│       ├── Expenses/
│       ├── Bills/
│       ├── Payments/
│       ├── BankReconciliation/
│       ├── Reports/
│       └── FiscalPeriods/
```

By **namespacing** the module under `Accounting/` in both backend and frontend, the module stays cleanly separated and can be toggled on/off without touching core code.

### Critical Design Principles

The following principles are **mandatory** across the entire accounting module:

| # | Principle | Rationale |
|---|---|---|
| 1 | **Soft Deletes** | All financial tables use `SoftDeletes`. Accounting records must never be hard-deleted — they are voided instead. Applies to: `journal_entries`, `journal_entry_lines`, `expenses`, `bills`, `bill_items`, `payments`. |
| 2 | **Multi-Currency** | Every financial transaction carries `currency_code` + `exchange_rate`. A `currencies` table stores available currencies per company. Base currency defaults to `IDR`. |
| 3 | **Audit Trail** | All CUD (Create/Update/Delete) + status change actions (post, void, approve) are logged to `audit_logs` for compliance and traceability. |
| 4 | **Form Request Validation** | All controller store/update actions use dedicated `FormRequest` classes under `App\Http\Requests\Accounting\`. No inline validation in controllers. |
| 5 | **BankAccount Scope Alignment** | The existing `BankAccount` model uses `user_id` + `BelongsToCompany` trait (already company-scoped). Accounting adds an `accounting_account_id` FK to link bank accounts to Chart of Accounts entries. |

---

## 2. Module Activation & Subscription Gating

### Migration: Add `addons` JSON Column to Users Table

```php
// 2026_02_18_000001_add_addons_to_users_table.php
Schema::table('users', function (Blueprint $table) {
    $table->json('addons')->nullable()->after('plan_renews_at');
});
```

The `addons` column stores a JSON array of active module slugs:
```json
["accounting", "payroll"]   // future modules can be added
```

### Middleware: `EnsureAccountingModule`

```php
// app/Http/Middleware/EnsureAccountingModule.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAccountingModule
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$this->hasAccountingAddon($user)) {
            if ($request->wantsJson() || $request->header('X-Inertia')) {
                abort(403, 'Accounting module is not active on your subscription.');
            }
            return redirect()->route('dashboard')
                ->with('error', 'Please subscribe to the Accounting addon to access this feature.');
        }

        return $next($request);
    }

    private function hasAccountingAddon($user): bool
    {
        $addons = $user->addons ?? [];
        return in_array('accounting', $addons);
    }
}
```

Register in `bootstrap/app.php` or `Kernel.php` as alias `accounting`.

### Helper on User Model

```php
// Add to app/Models/User.php
protected $casts = [
    // ...existing casts
    'addons' => 'array',
];

public function hasAddon(string $slug): bool
{
    return in_array($slug, $this->addons ?? []);
}

public function hasAccounting(): bool
{
    return $this->hasAddon('accounting');
}
```

---

## 3. Database Migrations

### Migration 1: `create_accounts_table` (Chart of Accounts)

```php
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
    $table->timestamps();

    $table->unique(['company_id', 'code']);
    $table->foreign('parent_id')->references('id')->on('accounts')->nullOnDelete();
});
```

### Migration 2: `create_fiscal_periods_table`

```php
Schema::create('fiscal_periods', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('company_id')->index();
    $table->string('name');                       // e.g., "FY 2026", "Jan 2026"
    $table->date('start_date');
    $table->date('end_date');
    $table->enum('status', ['open', 'closed', 'locked'])->default('open');
    $table->timestamps();

    $table->unique(['company_id', 'name']);
});
```

### Migration 3: `create_journal_entries_table`

```php
Schema::create('journal_entries', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('company_id')->index();
    $table->string('entry_number')->index();      // e.g., "JE-0001"
    $table->date('entry_date');
    $table->string('reference')->nullable();       // external ref
    $table->text('description')->nullable();
    $table->enum('status', ['draft', 'posted', 'voided'])->default('draft');
    $table->enum('source_type', [
        'manual', 'invoice', 'payment', 'expense', 'bill', 'adjustment'
    ])->default('manual');
    $table->unsignedBigInteger('source_id')->nullable(); // polymorphic-like FK
    $table->unsignedBigInteger('fiscal_period_id')->nullable();
    $table->unsignedBigInteger('created_by')->nullable();
    $table->timestamps();

    $table->unique(['company_id', 'entry_number']);
    $table->foreign('fiscal_period_id')->references('id')->on('fiscal_periods')->nullOnDelete();
    $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
});
```

### Migration 4: `create_journal_entry_lines_table`

```php
Schema::create('journal_entry_lines', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('journal_entry_id');
    $table->unsignedBigInteger('account_id');
    $table->text('description')->nullable();
    $table->decimal('debit', 15, 2)->default(0);
    $table->decimal('credit', 15, 2)->default(0);
    $table->timestamps();

    $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->cascadeOnDelete();
    $table->foreign('account_id')->references('id')->on('accounts');
    $table->index(['account_id', 'journal_entry_id']);
});
```

### Migration 5: `create_expense_categories_table`

```php
Schema::create('expense_categories', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('company_id')->index();
    $table->string('name');
    $table->string('color', 7)->default('#6B7280');  // hex color for UI
    $table->unsignedBigInteger('default_account_id')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->unique(['company_id', 'name']);
    $table->foreign('default_account_id')->references('id')->on('accounts')->nullOnDelete();
});
```

### Migration 6: `create_expenses_table`

```php
Schema::create('expenses', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('company_id')->index();
    $table->string('expense_number')->index();
    $table->unsignedBigInteger('expense_category_id')->nullable();
    $table->unsignedBigInteger('account_id');              // expense account
    $table->unsignedBigInteger('payment_account_id')->nullable(); // paid from
    $table->unsignedBigInteger('vendor_client_id')->nullable();   // optional vendor
    $table->date('expense_date');
    $table->decimal('amount', 15, 2);
    $table->decimal('tax_amount', 15, 2)->default(0);
    $table->decimal('total', 15, 2);
    $table->text('description')->nullable();
    $table->string('receipt_path')->nullable();            // uploaded receipt
    $table->enum('status', ['pending', 'approved', 'paid', 'voided'])->default('pending');
    $table->unsignedBigInteger('journal_entry_id')->nullable();
    $table->timestamps();

    $table->unique(['company_id', 'expense_number']);
    $table->foreign('expense_category_id')->references('id')->on('expense_categories')->nullOnDelete();
    $table->foreign('account_id')->references('id')->on('accounts');
    $table->foreign('payment_account_id')->references('id')->on('accounts')->nullOnDelete();
    $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
});
```

### Migration 7: `create_bills_table` (Accounts Payable)

```php
Schema::create('bills', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('company_id')->index();
    $table->string('bill_number')->index();
    $table->unsignedBigInteger('vendor_client_id')->nullable(); // vendor (reuse clients table or separate)
    $table->date('bill_date');
    $table->date('due_date');
    $table->decimal('subtotal', 15, 2)->default(0);
    $table->decimal('tax_total', 15, 2)->default(0);
    $table->decimal('total', 15, 2)->default(0);
    $table->decimal('amount_paid', 15, 2)->default(0);
    $table->text('notes')->nullable();
    $table->enum('status', ['draft', 'received', 'partially_paid', 'paid', 'overdue', 'voided'])->default('draft');
    $table->unsignedBigInteger('journal_entry_id')->nullable();
    $table->timestamps();

    $table->unique(['company_id', 'bill_number']);
    $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
});
```

### Migration 8: `create_bill_items_table`

```php
Schema::create('bill_items', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('bill_id');
    $table->unsignedBigInteger('account_id');   // expense/asset account to debit
    $table->string('description')->nullable();
    $table->integer('quantity')->default(1);
    $table->decimal('unit_price', 15, 2)->default(0);
    $table->decimal('subtotal', 15, 2)->default(0);
    $table->timestamps();

    $table->foreign('bill_id')->references('id')->on('bills')->cascadeOnDelete();
    $table->foreign('account_id')->references('id')->on('accounts');
});
```

### Migration 9: `create_payments_table`

```php
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('company_id')->index();
    $table->string('payment_number')->index();
    $table->enum('type', ['received', 'made']);            // AR vs AP
    $table->unsignedBigInteger('invoice_id')->nullable();  // links to existing invoices table
    $table->unsignedBigInteger('bill_id')->nullable();     // links to bills table
    $table->unsignedBigInteger('bank_account_id')->nullable();
    $table->unsignedBigInteger('deposit_account_id');      // account credited/debited
    $table->date('payment_date');
    $table->decimal('amount', 15, 2);
    $table->string('payment_method')->nullable();          // cash, bank_transfer, card, etc.
    $table->string('reference')->nullable();               // check #, transaction ID
    $table->text('notes')->nullable();
    $table->unsignedBigInteger('journal_entry_id')->nullable();
    $table->timestamps();

    $table->unique(['company_id', 'payment_number']);
    $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
    $table->foreign('bill_id')->references('id')->on('bills')->nullOnDelete();
    $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->nullOnDelete();
    $table->foreign('deposit_account_id')->references('id')->on('accounts');
    $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
});
```

### Migration 10: `create_bank_transactions_table`

```php
Schema::create('bank_transactions', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('company_id')->index();
    $table->unsignedBigInteger('bank_account_id');
    $table->date('transaction_date');
    $table->string('description')->nullable();
    $table->decimal('amount', 15, 2);                      // positive=deposit, negative=withdrawal
    $table->decimal('running_balance', 15, 2)->nullable();
    $table->string('reference')->nullable();
    $table->enum('status', ['unmatched', 'matched', 'excluded'])->default('unmatched');
    $table->unsignedBigInteger('matched_payment_id')->nullable();
    $table->unsignedBigInteger('matched_expense_id')->nullable();
    $table->timestamps();

    $table->foreign('bank_account_id')->references('id')->on('bank_accounts');
    $table->foreign('matched_payment_id')->references('id')->on('payments')->nullOnDelete();
    $table->foreign('matched_expense_id')->references('id')->on('expenses')->nullOnDelete();
});
```

### Migration 11: `create_bank_reconciliations_table`

```php
Schema::create('bank_reconciliations', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('company_id')->index();
    $table->unsignedBigInteger('bank_account_id');
    $table->date('statement_date');
    $table->decimal('statement_balance', 15, 2);
    $table->decimal('reconciled_balance', 15, 2)->default(0);
    $table->decimal('difference', 15, 2)->default(0);
    $table->enum('status', ['in_progress', 'completed'])->default('in_progress');
    $table->timestamps();

    $table->foreign('bank_account_id')->references('id')->on('bank_accounts');
});
```

### Migration 12: `create_currencies_table`

```php
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
```

### Migration 13: `create_audit_logs_table`

```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('company_id')->index();
    $table->unsignedBigInteger('user_id')->nullable();
    $table->string('auditable_type');             // e.g., App\Models\Accounting\JournalEntry
    $table->unsignedBigInteger('auditable_id');
    $table->string('action');                     // created, updated, deleted, posted, voided, approved
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->string('user_agent')->nullable();
    $table->timestamps();

    $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
    $table->index(['auditable_type', 'auditable_id']);
    $table->index('action');
});
```

### Migration 14: `alter_bank_accounts_add_accounting_fields`

```php
// Link existing BankAccount to Chart of Accounts for accounting integration
Schema::table('bank_accounts', function (Blueprint $table) {
    $table->unsignedBigInteger('accounting_account_id')->nullable()->after('is_default');
    $table->decimal('opening_balance', 15, 2)->default(0)->after('accounting_account_id');
    $table->foreign('accounting_account_id')->references('id')->on('accounts')->nullOnDelete();
});
```

> **Note:** The existing `user_id` on `bank_accounts` is kept for backwards compatibility. The `BelongsToCompany` trait already scopes by `company_id`, so bank accounts are accessible company-wide in the accounting module.

### Soft Deletes — Amendments to Existing Migrations

The following tables **must include** `$table->softDeletes()` before `$table->timestamps()`:

| Migration | Table |
|---|---|
| Migration 3 | `journal_entries` |
| Migration 4 | `journal_entry_lines` |
| Migration 6 | `expenses` |
| Migration 7 | `bills` |
| Migration 8 | `bill_items` |
| Migration 9 | `payments` |

### Multi-Currency — Amendments to Existing Migrations

The following tables **must include** currency fields after `company_id`:

```php
// Add to: journal_entries, expenses, bills, payments
$table->string('currency_code', 3)->default('IDR')->after('company_id');
$table->decimal('exchange_rate', 15, 6)->default(1)->after('currency_code');
```

| Migration | Table |
|---|---|
| Migration 3 | `journal_entries` |
| Migration 6 | `expenses` |
| Migration 7 | `bills` |
| Migration 9 | `payments` |

### Summary of All Migrations

| # | Migration File | Table Created |
|---|---|---|
| 1 | `add_addons_to_users_table` | Alter `users` |
| 2 | `create_accounts_table` | `accounts` |
| 3 | `create_fiscal_periods_table` | `fiscal_periods` |
| 4 | `create_journal_entries_table` | `journal_entries` *(+ softDeletes, currency)* |
| 5 | `create_journal_entry_lines_table` | `journal_entry_lines` *(+ softDeletes)* |
| 6 | `create_expense_categories_table` | `expense_categories` |
| 7 | `create_expenses_table` | `expenses` *(+ softDeletes, currency)* |
| 8 | `create_bills_table` | `bills` *(+ softDeletes, currency)* |
| 9 | `create_bill_items_table` | `bill_items` *(+ softDeletes)* |
| 10 | `create_payments_table` | `payments` *(+ softDeletes, currency)* |
| 11 | `create_bank_transactions_table` | `bank_transactions` |
| 12 | `create_bank_reconciliations_table` | `bank_reconciliations` |
| 13 | `create_currencies_table` | `currencies` |
| 14 | `create_audit_logs_table` | `audit_logs` |
| 15 | `alter_bank_accounts_add_accounting_fields` | Alter `bank_accounts` |

**Total: 15 migrations** (2 alter + 13 new tables)

---

## 4. Models & Relationships

All new models live under `App\Models\Accounting\` and use the `BelongsToCompany` trait.

### 4.1 `Account` (Chart of Accounts)

```
App\Models\Accounting\Account
├── belongsTo: parent (self)
├── hasMany: children (self)
├── hasMany: journalEntryLines (JournalEntryLine)
├── hasMany: expenses (Expense)
├── hasMany: billItems (BillItem)
Traits: BelongsToCompany
```

### 4.2 `FiscalPeriod`

```
App\Models\Accounting\FiscalPeriod
├── hasMany: journalEntries (JournalEntry)
Traits: BelongsToCompany
```

### 4.3 `JournalEntry`

```
App\Models\Accounting\JournalEntry
├── belongsTo: fiscalPeriod (FiscalPeriod)
├── belongsTo: createdBy (User)
├── hasMany: lines (JournalEntryLine)
Traits: BelongsToCompany
```

### 4.4 `JournalEntryLine`

```
App\Models\Accounting\JournalEntryLine
├── belongsTo: journalEntry (JournalEntry)
├── belongsTo: account (Account)
```

### 4.5 `ExpenseCategory`

```
App\Models\Accounting\ExpenseCategory
├── belongsTo: defaultAccount (Account)
├── hasMany: expenses (Expense)
Traits: BelongsToCompany
```

### 4.6 `Expense`

```
App\Models\Accounting\Expense
├── belongsTo: category (ExpenseCategory)
├── belongsTo: account (Account)
├── belongsTo: paymentAccount (Account)
├── belongsTo: journalEntry (JournalEntry)
├── belongsTo: vendor (Client) — nullable
Traits: BelongsToCompany
```

### 4.7 `Bill`

```
App\Models\Accounting\Bill
├── belongsTo: vendor (Client) — nullable
├── belongsTo: journalEntry (JournalEntry)
├── hasMany: items (BillItem)
├── hasMany: payments (Payment)
Traits: BelongsToCompany
```

### 4.8 `BillItem`

```
App\Models\Accounting\BillItem
├── belongsTo: bill (Bill)
├── belongsTo: account (Account)
```

### 4.9 `Payment`

```
App\Models\Accounting\Payment
├── belongsTo: invoice (Invoice) — existing model
├── belongsTo: bill (Bill)
├── belongsTo: bankAccount (BankAccount) — existing model
├── belongsTo: depositAccount (Account)
├── belongsTo: journalEntry (JournalEntry)
Traits: BelongsToCompany
```

### 4.10 `BankTransaction`

```
App\Models\Accounting\BankTransaction
├── belongsTo: bankAccount (BankAccount) — existing model
├── belongsTo: matchedPayment (Payment)
├── belongsTo: matchedExpense (Expense)
Traits: BelongsToCompany
```

### 4.11 `BankReconciliation`

```
App\Models\Accounting\BankReconciliation
├── belongsTo: bankAccount (BankAccount) — existing model
Traits: BelongsToCompany
```

### 4.12 `Currency`

```
App\Models\Accounting\Currency
Traits: BelongsToCompany
```

### 4.13 `AuditLog`

```
App\Models\Accounting\AuditLog
├── belongsTo: user (User)
├── morphTo: auditable
Traits: BelongsToCompany
```

### Soft Deletes on Models

The following models **must use** the `Illuminate\Database\Eloquent\SoftDeletes` trait:

| Model | Trait |
|---|---|
| `JournalEntry` | `use SoftDeletes;` |
| `JournalEntryLine` | `use SoftDeletes;` |
| `Expense` | `use SoftDeletes;` |
| `Bill` | `use SoftDeletes;` |
| `BillItem` | `use SoftDeletes;` |
| `Payment` | `use SoftDeletes;` |

### Multi-Currency on Models

The following models include `currency_code` and `exchange_rate` in `$fillable` and have a helper method `convertToBase()`:

| Model | Fields Added |
|---|---|
| `JournalEntry` | `currency_code`, `exchange_rate` |
| `Expense` | `currency_code`, `exchange_rate` |
| `Bill` | `currency_code`, `exchange_rate` |
| `Payment` | `currency_code`, `exchange_rate` |

### Relationships to Add on Existing Models

| Existing Model | New Relationship |
|---|---|
| `Invoice` | `hasMany: payments` → `Accounting\Payment` |
| `Invoice` | `hasOne: journalEntry` → through payment |
| `BankAccount` | `hasMany: bankTransactions` → `Accounting\BankTransaction` |
| `BankAccount` | `hasMany: reconciliations` → `Accounting\BankReconciliation` |
| `BankAccount` | `belongsTo: accountingAccount` → `Accounting\Account` |
| `Client` | `hasMany: bills` → `Accounting\Bill` (as vendor) |
| `Client` | `hasMany: expenses` → `Accounting\Expense` (as vendor) |

---

## 5. Controllers

All controllers under `App\Http\Controllers\Accounting\`.

| Controller | Actions | Description |
|---|---|---|
| `ChartOfAccountController` | `index`, `store`, `update`, `destroy`, `tree` | CRUD + tree view of accounts |
| `JournalEntryController` | `index`, `create`, `store`, `show`, `update`, `post`, `void` | Journal entries with posting workflow |
| `ExpenseCategoryController` | `index`, `store`, `update`, `destroy` | Manage expense categories |
| `ExpenseController` | `index`, `create`, `store`, `show`, `update`, `approve`, `void` | Full expense lifecycle |
| `BillController` | `index`, `create`, `store`, `show`, `update` | Accounts payable bills |
| `PaymentController` | `index`, `create`, `store`, `show` | Record payments (received & made) |
| `BankReconciliationController` | `index`, `create`, `store`, `match`, `complete` | Bank reconciliation workflow |
| `ReportController` | `profitLoss`, `balanceSheet`, `trialBalance`, `generalLedger`, `cashFlow` | Financial reports |
| `FiscalPeriodController` | `index`, `store`, `update`, `close`, `lock` | Period management |
| `AccountingDashboardController` | `index` | Module-specific dashboard with KPIs |

**Total: 10 controllers**

### Form Request Validation Classes

All store/update actions use dedicated `FormRequest` classes under `App\Http\Requests\Accounting\`:

| Form Request | Used By | Key Validation Rules |
|---|---|---|
| `StoreAccountRequest` | `ChartOfAccountController@store` | `code` unique per company, valid `type`↔`subtype` mapping |
| `UpdateAccountRequest` | `ChartOfAccountController@update` | Same as store, exclude self from unique check |
| `StoreJournalEntryRequest` | `JournalEntryController@store` | `lines` array min:2, total debits == total credits, `entry_date` within open fiscal period, all `account_id` belong to company |
| `UpdateJournalEntryRequest` | `JournalEntryController@update` | Same as store; only allowed if status == `draft` |
| `StoreExpenseRequest` | `ExpenseController@store` | `account_id` must be `expense` type, `amount` > 0, valid date, receipt file max 5MB |
| `UpdateExpenseRequest` | `ExpenseController@update` | Same as store; only allowed if status == `pending` |
| `StoreBillRequest` | `BillController@store` | `items` array required, `due_date` >= `bill_date`, all `account_id` valid |
| `UpdateBillRequest` | `BillController@update` | Same as store; only allowed if status == `draft` |
| `StorePaymentRequest` | `PaymentController@store` | `amount` > 0, `amount` <= remaining balance on invoice/bill, valid `deposit_account_id` |
| `StoreFiscalPeriodRequest` | `FiscalPeriodController@store` | `start_date` < `end_date`, no overlapping periods for same company |
| `StoreBankReconciliationRequest` | `BankReconciliationController@store` | `bank_account_id` belongs to company, valid statement date |

**Total: 11 Form Request classes**

#### Example: `StoreJournalEntryRequest`

```php
// app/Http/Requests/Accounting/StoreJournalEntryRequest.php
namespace App\Http\Requests\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\FiscalPeriod;
use Illuminate\Foundation\Http\FormRequest;

class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // handled by middleware
    }

    public function rules(): array
    {
        return [
            'entry_date'            => 'required|date',
            'description'           => 'nullable|string|max:1000',
            'reference'             => 'nullable|string|max:255',
            'currency_code'         => 'required|string|size:3|exists:currencies,code',
            'lines'                 => 'required|array|min:2',
            'lines.*.account_id'    => 'required|exists:accounts,id',
            'lines.*.description'   => 'nullable|string|max:500',
            'lines.*.debit'         => 'required|numeric|min:0',
            'lines.*.credit'        => 'required|numeric|min:0',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $lines = $this->input('lines', []);

            // 1. Validate debit == credit
            $totalDebit = collect($lines)->sum('debit');
            $totalCredit = collect($lines)->sum('credit');
            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                $validator->errors()->add('lines',
                    "Total debit ({$totalDebit}) must equal total credit ({$totalCredit}).");
            }

            // 2. Each line must have either debit OR credit, not both
            foreach ($lines as $i => $line) {
                if (($line['debit'] ?? 0) > 0 && ($line['credit'] ?? 0) > 0) {
                    $validator->errors()->add("lines.{$i}",
                        'A line cannot have both debit and credit values.');
                }
                if (($line['debit'] ?? 0) == 0 && ($line['credit'] ?? 0) == 0) {
                    $validator->errors()->add("lines.{$i}",
                        'A line must have either a debit or credit value.');
                }
            }

            // 3. Validate entry_date is within an open fiscal period
            $entryDate = $this->input('entry_date');
            if ($entryDate) {
                $period = FiscalPeriod::where('company_id', auth()->user()->company_id)
                    ->where('start_date', '<=', $entryDate)
                    ->where('end_date', '>=', $entryDate)
                    ->first();

                if ($period && $period->status !== 'open') {
                    $validator->errors()->add('entry_date',
                        "Fiscal period '{$period->name}' is {$period->status}.");
                }
            }

            // 4. Validate all account_ids belong to the user's company
            $accountIds = collect($lines)->pluck('account_id')->filter()->unique();
            $validCount = Account::whereIn('id', $accountIds)
                ->where('company_id', auth()->user()->company_id)
                ->count();
            if ($validCount !== $accountIds->count()) {
                $validator->errors()->add('lines',
                    'One or more accounts do not belong to your company.');
            }
        });
    }
}
```

---

## 6. Routes

All routes gated behind `auth`, `verified_account`, and `accounting` middleware.

```php
// routes/web.php — add inside the verified_account middleware group

Route::middleware('accounting')->prefix('accounting')->name('accounting.')->group(function () {

    // Dashboard
    Route::get('/', [Accounting\AccountingDashboardController::class, 'index'])
        ->name('dashboard');

    // Chart of Accounts
    Route::get('/accounts/tree', [Accounting\ChartOfAccountController::class, 'tree'])
        ->name('accounts.tree');
    Route::resource('accounts', Accounting\ChartOfAccountController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    // Fiscal Periods
    Route::post('/fiscal-periods/{fiscalPeriod}/close', [Accounting\FiscalPeriodController::class, 'close'])
        ->name('fiscal-periods.close');
    Route::post('/fiscal-periods/{fiscalPeriod}/lock', [Accounting\FiscalPeriodController::class, 'lock'])
        ->name('fiscal-periods.lock');
    Route::resource('fiscal-periods', Accounting\FiscalPeriodController::class)
        ->only(['index', 'store', 'update']);

    // Journal Entries
    Route::post('/journal-entries/{journalEntry}/post', [Accounting\JournalEntryController::class, 'post'])
        ->name('journal-entries.post');
    Route::post('/journal-entries/{journalEntry}/void', [Accounting\JournalEntryController::class, 'void'])
        ->name('journal-entries.void');
    Route::resource('journal-entries', Accounting\JournalEntryController::class)
        ->only(['index', 'create', 'store', 'show', 'update']);

    // Expense Categories
    Route::resource('expense-categories', Accounting\ExpenseCategoryController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    // Expenses
    Route::post('/expenses/{expense}/approve', [Accounting\ExpenseController::class, 'approve'])
        ->name('expenses.approve');
    Route::post('/expenses/{expense}/void', [Accounting\ExpenseController::class, 'void'])
        ->name('expenses.void');
    Route::resource('expenses', Accounting\ExpenseController::class)
        ->only(['index', 'create', 'store', 'show', 'update']);

    // Bills (Accounts Payable)
    Route::resource('bills', Accounting\BillController::class)
        ->only(['index', 'create', 'store', 'show', 'update']);

    // Payments
    Route::resource('payments', Accounting\PaymentController::class)
        ->only(['index', 'create', 'store', 'show']);

    // Bank Reconciliation
    Route::post('/bank-reconciliation/{reconciliation}/match', [Accounting\BankReconciliationController::class, 'match'])
        ->name('bank-reconciliation.match');
    Route::post('/bank-reconciliation/{reconciliation}/complete', [Accounting\BankReconciliationController::class, 'complete'])
        ->name('bank-reconciliation.complete');
    Route::resource('bank-reconciliation', Accounting\BankReconciliationController::class)
        ->only(['index', 'create', 'store']);

    // Financial Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/profit-loss', [Accounting\ReportController::class, 'profitLoss'])
            ->name('profit-loss');
        Route::get('/balance-sheet', [Accounting\ReportController::class, 'balanceSheet'])
            ->name('balance-sheet');
        Route::get('/trial-balance', [Accounting\ReportController::class, 'trialBalance'])
            ->name('trial-balance');
        Route::get('/general-ledger', [Accounting\ReportController::class, 'generalLedger'])
            ->name('general-ledger');
        Route::get('/cash-flow', [Accounting\ReportController::class, 'cashFlow'])
            ->name('cash-flow');
    });
});
```

### Route Summary

| Prefix | Named As | Methods |
|---|---|---|
| `/accounting` | `accounting.dashboard` | GET |
| `/accounting/accounts` | `accounting.accounts.*` | GET, POST, PUT, DELETE |
| `/accounting/accounts/tree` | `accounting.accounts.tree` | GET |
| `/accounting/fiscal-periods` | `accounting.fiscal-periods.*` | GET, POST, PUT + close, lock |
| `/accounting/journal-entries` | `accounting.journal-entries.*` | GET, POST, PUT + post, void |
| `/accounting/expense-categories` | `accounting.expense-categories.*` | GET, POST, PUT, DELETE |
| `/accounting/expenses` | `accounting.expenses.*` | GET, POST, PUT + approve, void |
| `/accounting/bills` | `accounting.bills.*` | GET, POST, PUT |
| `/accounting/payments` | `accounting.payments.*` | GET, POST |
| `/accounting/bank-reconciliation` | `accounting.bank-reconciliation.*` | GET, POST + match, complete |
| `/accounting/reports/*` | `accounting.reports.*` | 5× GET |

**Total: ~35 route endpoints**

---

## 7. Service Classes

### 7.1 `JournalService`

Responsible for creating, posting, and voiding journal entries. The **core engine** of the module.

```php
namespace App\Services\Accounting;

class JournalService
{
    // Create a journal entry with lines (validates debits == credits)
    public function createEntry(array $data, array $lines): JournalEntry;

    // Post a draft entry (updates account balances)
    public function postEntry(JournalEntry $entry): void;

    // Void a posted entry (creates reversal entry)
    public function voidEntry(JournalEntry $entry): JournalEntry;

    // Auto-create journal entry from invoice creation
    public function createFromInvoice(Invoice $invoice): JournalEntry;

    // Auto-create journal entry from payment
    public function createFromPayment(Payment $payment): JournalEntry;

    // Auto-create journal entry from expense
    public function createFromExpense(Expense $expense): JournalEntry;

    // Auto-create journal entry from bill
    public function createFromBill(Bill $bill): JournalEntry;
}
```

### 7.2 `LedgerService`

```php
class LedgerService
{
    // Get ledger for a specific account with date range
    public function getAccountLedger(Account $account, ?Carbon $from, ?Carbon $to): Collection;

    // Get running balance for an account up to a date
    public function getBalanceAsOf(Account $account, Carbon $date): float;

    // Recalculate all account balances (admin utility)
    public function recalculateBalances(): void;
}
```

### 7.3 `ReportService`

```php
class ReportService
{
    public function profitAndLoss(Carbon $from, Carbon $to): array;
    public function balanceSheet(Carbon $asOf): array;
    public function trialBalance(Carbon $asOf): array;
    public function cashFlowStatement(Carbon $from, Carbon $to): array;
}
```

### 7.4 `ReconciliationService`

```php
class ReconciliationService
{
    // Auto-match bank transactions to payments/expenses
    public function autoMatch(BankReconciliation $reconciliation): int;

    // Manually match a transaction
    public function manualMatch(BankTransaction $txn, string $type, int $id): void;

    // Complete reconciliation
    public function complete(BankReconciliation $reconciliation): void;
}
```

### 7.5 `AuditService`

Responsible for logging all accounting actions. Provides a consistent audit trail for compliance.

```php
namespace App\Services\Accounting;

use App\Models\Accounting\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    /**
     * Log a create/update/delete/status-change action.
     */
    public function log(
        Model $model,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog;

    /**
     * Convenience methods for common actions.
     */
    public function logCreated(Model $model): AuditLog;
    public function logUpdated(Model $model, array $oldValues): AuditLog;
    public function logDeleted(Model $model): AuditLog;
    public function logStatusChange(Model $model, string $action, string $from, string $to): AuditLog;

    /**
     * Get audit history for a model instance.
     */
    public function getHistory(Model $model): Collection;
}
```

### 7.6 `CurrencyService`

Manages currency operations and exchange rate conversions.

```php
namespace App\Services\Accounting;

use App\Models\Accounting\Currency;

class CurrencyService
{
    // Get the base currency for a company
    public function getBaseCurrency(int $companyId): Currency;

    // Convert an amount from one currency to base currency
    public function convertToBase(float $amount, string $currencyCode, int $companyId): float;

    // Convert between two currencies
    public function convert(float $amount, string $fromCode, string $toCode, int $companyId): float;

    // Seed default currencies for a company (IDR as base + common currencies)
    public function seedDefaults(int $companyId): void;
}
```

---

## 8. Middleware

| Middleware | Alias | Purpose |
|---|---|---|
| `EnsureAccountingModule` | `accounting` | Gates all `/accounting/*` routes; checks `user.addons` contains `"accounting"` |

Existing middleware remains unchanged:
- `EnsureAccountVerified` → `verified_account`
- `EnsureRole` → `role`

---

## 9. Vue Pages & Components

### Page Structure

```
resources/js/Pages/Accounting/
├── Dashboard.vue                    — Module dashboard with KPIs & charts
├── ChartOfAccounts/
│   └── Index.vue                    — Tree/table view + CRUD modals
├── JournalEntries/
│   ├── Index.vue                    — Datatable list
│   ├── Create.vue                   — Multi-line debit/credit form
│   └── Show.vue                     — Entry detail + post/void actions
├── Expenses/
│   ├── Index.vue                    — Datatable with filters by category
│   ├── Create.vue                   — Expense form with receipt upload
│   └── Show.vue                     — Expense detail
├── Bills/
│   ├── Index.vue                    — Datatable of bills
│   ├── Create.vue                   — Multi-line bill form
│   └── Show.vue                     — Bill detail + payment recording
├── Payments/
│   ├── Index.vue                    — All payments (received & made)
│   ├── Create.vue                   — Payment recording form
│   └── Show.vue                     — Payment detail
├── BankReconciliation/
│   ├── Index.vue                    — List of reconciliations
│   └── Reconcile.vue                — Interactive matching UI
├── Reports/
│   ├── ProfitLoss.vue               — P&L report with date range
│   ├── BalanceSheet.vue             — Balance sheet as-of date
│   ├── TrialBalance.vue             — Trial balance
│   ├── GeneralLedger.vue            — Ledger per account
│   └── CashFlow.vue                 — Cash flow statement
└── FiscalPeriods/
    └── Index.vue                    — Period management
```

### Shared Components

```
resources/js/Components/Accounting/
├── AccountSelector.vue              — Searchable account dropdown
├── DebitCreditTable.vue             — Reusable debit/credit line editor
├── AccountBadge.vue                 — Color-coded account type badge
├── CurrencyDisplay.vue              — Formatted currency display
└── ReportFilters.vue                — Date range & filter controls
```

### Navigation

Add an **"Accounting"** section to the `AppLayout.vue` sidebar, conditionally rendered based on `$page.props.auth.user.addons`:

```vue
<template v-if="hasAccounting">
  <SidebarSection title="Accounting">
    <SidebarLink :href="route('accounting.dashboard')" icon="mdi:calculator">Dashboard</SidebarLink>
    <SidebarLink :href="route('accounting.accounts.index')" icon="mdi:file-tree">Chart of Accounts</SidebarLink>
    <SidebarLink :href="route('accounting.journal-entries.index')" icon="mdi:book-open-page-variant">Journal Entries</SidebarLink>
    <SidebarLink :href="route('accounting.expenses.index')" icon="mdi:receipt-text">Expenses</SidebarLink>
    <SidebarLink :href="route('accounting.bills.index')" icon="mdi:file-document-outline">Bills</SidebarLink>
    <SidebarLink :href="route('accounting.payments.index')" icon="mdi:cash-multiple">Payments</SidebarLink>
    <SidebarLink :href="route('accounting.bank-reconciliation.index')" icon="mdi:bank-transfer">Bank Reconciliation</SidebarLink>
    <SidebarCollapsible title="Reports" icon="mdi:chart-bar">
      <SidebarLink :href="route('accounting.reports.profit-loss')">Profit & Loss</SidebarLink>
      <SidebarLink :href="route('accounting.reports.balance-sheet')">Balance Sheet</SidebarLink>
      <SidebarLink :href="route('accounting.reports.trial-balance')">Trial Balance</SidebarLink>
      <SidebarLink :href="route('accounting.reports.general-ledger')">General Ledger</SidebarLink>
      <SidebarLink :href="route('accounting.reports.cash-flow')">Cash Flow</SidebarLink>
    </SidebarCollapsible>
    <SidebarLink :href="route('accounting.fiscal-periods.index')" icon="mdi:calendar-lock">Fiscal Periods</SidebarLink>
  </SidebarSection>
</template>
```

---

## 10. Implementation Phases

### Phase 1: Foundation (Week 1)
> Priority: 🔴 Critical

| Task | Files |
|---|---|
| Add `addons` column migration | `2026_02_18_000001_add_addons_to_users_table.php` |
| Create `EnsureAccountingModule` middleware | `app/Http/Middleware/EnsureAccountingModule.php` |
| Register middleware alias | `bootstrap/app.php` |
| Update `User` model (addons cast + helpers) | `app/Models/User.php` |
| Create `accounts` migration + `Account` model | Migration + `app/Models/Accounting/Account.php` |
| Create `fiscal_periods` migration + `FiscalPeriod` model | Migration + Model |
| Create `ChartOfAccountController` with CRUD | Controller |
| Create `FiscalPeriodController` | Controller |
| Create default Chart of Accounts seeder | `database/seeders/DefaultAccountsSeeder.php` |
| Add accounting routes (accounts + fiscal periods) | `routes/web.php` |
| Create `ChartOfAccounts/Index.vue` | Vue page |
| Create `FiscalPeriods/Index.vue` | Vue page |
| Update `AppLayout.vue` sidebar navigation | Conditional accounting section |
| Create `currencies` migration + `Currency` model | Migration + `app/Models/Accounting/Currency.php` |
| Create `audit_logs` migration + `AuditLog` model | Migration + `app/Models/Accounting/AuditLog.php` |
| Create `AuditService` | `app/Services/Accounting/AuditService.php` |
| Create `CurrencyService` + seed defaults | `app/Services/Accounting/CurrencyService.php` |
| Alter `bank_accounts` migration (add `accounting_account_id`) | Migration |
| Create `StoreAccountRequest` + `UpdateAccountRequest` | `app/Http/Requests/Accounting/` |
| Create `StoreFiscalPeriodRequest` | `app/Http/Requests/Accounting/` |

### Phase 2: Journal Engine (Week 2)
> Priority: 🔴 Critical

| Task | Files |
|---|---|
| Create `journal_entries` + `journal_entry_lines` migrations | Migrations |
| Create `JournalEntry` + `JournalEntryLine` models | Models |
| Create `JournalService` | `app/Services/Accounting/JournalService.php` |
| Create `LedgerService` | `app/Services/Accounting/LedgerService.php` |
| Create `JournalEntryController` | Controller |
| Add journal entry routes | Routes |
| Create `JournalEntries/Index.vue` | Vue page |
| Create `JournalEntries/Create.vue` | Vue page (debit/credit form) |
| Create `JournalEntries/Show.vue` | Vue page |
| Create `DebitCreditTable.vue` component | Shared component |
| Create `AccountSelector.vue` component | Shared component |
| Create `StoreJournalEntryRequest` + `UpdateJournalEntryRequest` | `app/Http/Requests/Accounting/` |
| Add `SoftDeletes` trait to `JournalEntry` + `JournalEntryLine` | Models |
| Add `currency_code`, `exchange_rate` to `JournalEntry` | Model + migration |
| Integrate `AuditService` into `JournalEntryController` | Controller |

### Phase 3: Expenses & Bills (Week 3)
> Priority: 🟡 Important

| Task | Files |
|---|---|
| Create expense-related migrations (categories + expenses) | Migrations |
| Create `ExpenseCategory` + `Expense` models | Models |
| Create `ExpenseCategoryController` + `ExpenseController` | Controllers |
| Create bills-related migrations (bills + bill_items) | Migrations |
| Create `Bill` + `BillItem` models | Models |
| Create `BillController` | Controller |
| Add routes for expenses + bills | Routes |
| Create `Expenses/` pages (Index, Create, Show) | Vue pages |
| Create `Bills/` pages (Index, Create, Show) | Vue pages |
| Create `StoreExpenseRequest` + `UpdateExpenseRequest` | `app/Http/Requests/Accounting/` |
| Create `StoreBillRequest` + `UpdateBillRequest` | `app/Http/Requests/Accounting/` |
| Add `SoftDeletes` trait to `Expense`, `Bill`, `BillItem` | Models |
| Add `currency_code`, `exchange_rate` to `Expense`, `Bill` | Models + migrations |
| Integrate `AuditService` into `ExpenseController`, `BillController` | Controllers |

### Phase 4: Payments & Integration (Week 4)
> Priority: 🟡 Important

| Task | Files |
|---|---|
| Create `payments` migration | Migration |
| Create `Payment` model | Model |
| Create `PaymentController` | Controller |
| Add payment routes | Routes |
| Integrate `JournalService` auto-entries on invoice/payment/expense/bill | Service updates |
| Add `payments` relationship to existing `Invoice` model | Model update |
| Create `Payments/` pages (Index, Create, Show) | Vue pages |
| Update existing invoice flow to optionally record payments | Invoice updates |
| Create `StorePaymentRequest` | `app/Http/Requests/Accounting/` |
| Add `SoftDeletes` trait to `Payment` | Model |
| Add `currency_code`, `exchange_rate` to `Payment` | Model + migration |
| Integrate `AuditService` into `PaymentController` | Controller |

### Phase 5: Bank Reconciliation (Week 5)
> Priority: 🟢 Nice to have

| Task | Files |
|---|---|
| Create `bank_transactions` + `bank_reconciliations` migrations | Migrations |
| Create `BankTransaction` + `BankReconciliation` models | Models |
| Create `ReconciliationService` | Service |
| Create `BankReconciliationController` | Controller |
| Add bank reconciliation routes | Routes |
| Create `BankReconciliation/` pages (Index, Reconcile) | Vue pages |
| Add relationships to existing `BankAccount` model | Model update |

### Phase 6: Financial Reports (Week 5-6)
> Priority: 🟡 Important

| Task | Files |
|---|---|
| Create `ReportService` | Service |
| Create `ReportController` | Controller |
| Add report routes | Routes |
| Create `Reports/ProfitLoss.vue` | Vue page |
| Create `Reports/BalanceSheet.vue` | Vue page |
| Create `Reports/TrialBalance.vue` | Vue page |
| Create `Reports/GeneralLedger.vue` | Vue page |
| Create `Reports/CashFlow.vue` | Vue page |
| Create `Accounting/Dashboard.vue` with KPI cards | Vue page |

### Phase 7: Polish & Module Dashboard (Week 6)
> Priority: 🟢 Nice to have

| Task | Files |
|---|---|
| Accounting module dashboard with KPIs | Dashboard.vue |
| PDF export for reports | ReportController / service |
| CSV import for bank transactions | BankReconciliationController |
| Subscription/addon purchase UI flow | Profile/billing page |
| Automated test coverage | Feature tests |

---

## Summary Table

| Category | Count |
|---|---|
| **New Migrations** | 15 *(+3: currencies, audit_logs, alter bank_accounts)* |
| **New Models** | 13 *(+2: Currency, AuditLog)* |
| **New Controllers** | 10 |
| **New Service Classes** | 6 *(+2: AuditService, CurrencyService)* |
| **New Form Request Classes** | 11 |
| **New Middleware** | 1 |
| **New Route Endpoints** | ~35 |
| **New Vue Pages** | ~20 |
| **New Vue Components** | ~5 |
| **Existing Model Updates** | 4 (Invoice, BankAccount, Client, User) |

---

## 11. Auto-Seeding Chart of Accounts (CoA Templates)

### 11.1 Design Philosophy

Users should **never** have to manually build a Chart of Accounts from scratch. When activating the accounting module, the system presents an **onboarding step** that lets the user pick a CoA template suited to their business type. The selected template is auto-seeded immediately, and the user can customize (add/edit/delete accounts) later.

### 11.2 Available Templates

The system ships with **4 industry-specific templates**. Each template shares a **common base** (core accounts every business needs) plus industry-specific accounts.

---

#### 🏢 Template 1: `general` — General / Standard Business *(Default)*

The universal starter template. Suitable for most small-to-medium businesses.

| Code | Name | Type | Subtype | System? |
|---|---|---|---|---|
| **ASSETS** |||||
| 1000 | Cash | asset | current_asset | ✅ |
| 1010 | Petty Cash | asset | current_asset | |
| 1100 | Bank Account | asset | current_asset | ✅ |
| 1200 | Accounts Receivable | asset | current_asset | ✅ |
| 1300 | Inventory | asset | current_asset | |
| 1400 | Prepaid Expenses | asset | current_asset | |
| 1500 | Equipment | asset | fixed_asset | |
| 1510 | Accumulated Depreciation – Equipment | asset | fixed_asset | |
| 1600 | Furniture & Fixtures | asset | fixed_asset | |
| 1610 | Accumulated Depreciation – Furniture | asset | fixed_asset | |
| 1700 | Vehicles | asset | fixed_asset | |
| 1710 | Accumulated Depreciation – Vehicles | asset | fixed_asset | |
| 1800 | Security Deposits | asset | other_asset | |
| **LIABILITIES** |||||
| 2000 | Accounts Payable | liability | current_liability | ✅ |
| 2100 | Credit Card Payable | liability | current_liability | |
| 2200 | Sales Tax Payable (PPN) | liability | current_liability | ✅ |
| 2300 | Income Tax Payable (PPh) | liability | current_liability | |
| 2400 | Accrued Liabilities | liability | current_liability | |
| 2500 | Employee Payable / Wages Payable | liability | current_liability | |
| 2600 | Unearned Revenue | liability | current_liability | |
| 2700 | Short-Term Loans | liability | current_liability | |
| 2800 | Long-Term Loans | liability | long_term_liability | |
| **EQUITY** |||||
| 3000 | Owner's Equity / Capital | equity | owner_equity | ✅ |
| 3100 | Retained Earnings | equity | retained_earnings | ✅ |
| 3200 | Owner's Drawing | equity | owner_equity | |
| **REVENUE** |||||
| 4000 | Sales Revenue | revenue | operating_revenue | ✅ |
| 4100 | Service Revenue | revenue | operating_revenue | ✅ |
| 4200 | Discount Given | revenue | operating_revenue | |
| 4300 | Sales Returns & Allowances | revenue | operating_revenue | |
| 4800 | Interest Income | revenue | other_revenue | |
| 4900 | Other Income | revenue | other_revenue | |
| **EXPENSES** |||||
| 5000 | Cost of Goods Sold (COGS) | expense | cost_of_goods | ✅ |
| 5100 | Direct Labor | expense | cost_of_goods | |
| 5200 | Shipping & Delivery Costs | expense | cost_of_goods | |
| 6000 | Salaries & Wages | expense | operating_expense | |
| 6010 | Employee Benefits | expense | operating_expense | |
| 6100 | Rent Expense | expense | operating_expense | |
| 6200 | Utilities (Electricity, Water, Internet) | expense | operating_expense | |
| 6300 | Office Supplies | expense | operating_expense | |
| 6400 | Marketing & Advertising | expense | operating_expense | |
| 6500 | Insurance Expense | expense | operating_expense | |
| 6600 | Depreciation Expense | expense | operating_expense | |
| 6700 | Professional & Legal Fees | expense | operating_expense | |
| 6800 | Travel & Transportation | expense | operating_expense | |
| 6810 | Meals & Entertainment | expense | operating_expense | |
| 6900 | Repairs & Maintenance | expense | operating_expense | |
| 7000 | Bank Fees & Charges | expense | other_expense | |
| 7100 | Tax Expense | expense | other_expense | |
| 7900 | Miscellaneous Expense | expense | other_expense | |

**Total: ~50 accounts**

---

#### 🛠️ Template 2: `service` — Service-Based Business

For consulting firms, agencies, freelancers, IT services, professional services. De-emphasizes inventory, adds project & time-based revenue.

**Inherits all `general` accounts**, plus adds/replaces:

| Code | Name | Type | Subtype | Note |
|---|---|---|---|---|
| 4010 | Consulting Revenue | revenue | operating_revenue | *additional* |
| 4020 | Project Revenue | revenue | operating_revenue | *additional* |
| 4030 | Retainer Revenue | revenue | operating_revenue | *additional* |
| 4040 | Training & Workshop Revenue | revenue | operating_revenue | *additional* |
| 5100 | Direct Project Costs | expense | cost_of_goods | *replaces Direct Labor* |
| 5110 | Subcontractor Costs | expense | cost_of_goods | *additional* |
| 5120 | Software & Tools (Project) | expense | cost_of_goods | *additional* |
| 6050 | Contractor Payments | expense | operating_expense | *additional* |
| 6350 | Software Subscriptions | expense | operating_expense | *additional* |
| 6360 | Cloud & Hosting | expense | operating_expense | *additional* |
| 6820 | Client Entertainment | expense | operating_expense | *additional* |

**Removes:** `1300 Inventory` (not relevant for service businesses)

**Total: ~55 accounts**

---

#### 🛒 Template 3: `retail` — Retail / Trading Business

For shops, e-commerce, wholesale, distribution. Emphasizes inventory, COGS, and purchase-related accounts.

**Inherits all `general` accounts**, plus adds:

| Code | Name | Type | Subtype | Note |
|---|---|---|---|---|
| 1310 | Merchandise Inventory | asset | current_asset | *additional* |
| 1320 | Inventory in Transit | asset | current_asset | *additional* |
| 1330 | Inventory Shrinkage Allowance | asset | current_asset | *additional* |
| 4010 | Online Sales Revenue | revenue | operating_revenue | *additional* |
| 4020 | Wholesale Revenue | revenue | operating_revenue | *additional* |
| 4210 | Purchase Discounts | revenue | other_revenue | *additional* |
| 5010 | Purchases | expense | cost_of_goods | *additional* |
| 5020 | Purchase Returns & Allowances | expense | cost_of_goods | *additional* |
| 5030 | Freight-In / Import Costs | expense | cost_of_goods | *additional* |
| 5040 | Customs & Duties | expense | cost_of_goods | *additional* |
| 5050 | Packaging Costs | expense | cost_of_goods | *additional* |
| 5210 | Warehouse Rent | expense | cost_of_goods | *additional* |
| 6410 | Store Signage & Displays | expense | operating_expense | *additional* |
| 6420 | Point of Sale System | expense | operating_expense | *additional* |

**Total: ~60 accounts**

---

#### 🎨 Template 4: `creative` — Creative / Freelance Business

For designers, photographers, videographers, writers, artists. Simple and lean.

**Subset of `general` accounts**, plus adds:

| Code | Name | Type | Subtype | Note |
|---|---|---|---|---|
| 4010 | Design Services Revenue | revenue | operating_revenue | *additional* |
| 4020 | Photography / Video Revenue | revenue | operating_revenue | *additional* |
| 4030 | Licensing & Royalties | revenue | operating_revenue | *additional* |
| 4040 | Content Creation Revenue | revenue | operating_revenue | *additional* |
| 5110 | Stock Photos / Assets | expense | cost_of_goods | *additional* |
| 5120 | Printing & Production | expense | cost_of_goods | *additional* |
| 6350 | Software Subscriptions (Adobe, etc.) | expense | operating_expense | *additional* |
| 6360 | Equipment Rental | expense | operating_expense | *additional* |
| 6370 | Portfolio & Website Hosting | expense | operating_expense | *additional* |
| 6820 | Client Meeting Expenses | expense | operating_expense | *additional* |

**Removes:** `1300 Inventory`, `1700 Vehicles`, `5200 Shipping`

**Total: ~48 accounts**

---

### 11.3 Service Class: `ChartOfAccountSeeder`

```php
// app/Services/Accounting/ChartOfAccountSeeder.php
namespace App\Services\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\ExpenseCategory;

class ChartOfAccountSeeder
{
    /**
     * Available template keys.
     */
    public const TEMPLATES = [
        'general'  => 'General / Standard Business',
        'service'  => 'Service-Based Business',
        'retail'   => 'Retail / Trading Business',
        'creative' => 'Creative / Freelance',
    ];

    /**
     * Seed the Chart of Accounts for a company based on the selected template.
     */
    public function seed(int $companyId, string $template = 'general'): int
    {
        $accounts = $this->getTemplate($template);
        $count = 0;

        foreach ($accounts as $accountData) {
            Account::create([
                'company_id'      => $companyId,
                'code'            => $accountData['code'],
                'name'            => $accountData['name'],
                'type'            => $accountData['type'],
                'subtype'         => $accountData['subtype'],
                'description'     => $accountData['description'] ?? null,
                'is_system'       => $accountData['is_system'] ?? false,
                'is_active'       => true,
                'opening_balance' => 0,
                'current_balance' => 0,
            ]);
            $count++;
        }

        // Also seed default expense categories linked to expense accounts
        $this->seedExpenseCategories($companyId);

        return $count;
    }

    /**
     * Get list of available templates with labels.
     */
    public static function getAvailableTemplates(): array
    {
        return self::TEMPLATES;
    }

    /**
     * Get account definitions for a template.
     */
    private function getTemplate(string $template): array
    {
        $base = $this->baseAccounts();

        return match ($template) {
            'service'  => $this->mergeTemplate($base, $this->serviceAccounts()),
            'retail'   => $this->mergeTemplate($base, $this->retailAccounts()),
            'creative' => $this->mergeTemplate($base, $this->creativeAccounts()),
            default    => $base,
        };
    }

    private function mergeTemplate(array $base, array $overrides): array
    {
        $removeCodes = collect($overrides)->where('_action', 'remove')->pluck('code')->toArray();
        $additions   = collect($overrides)->where('_action', '!=', 'remove')->toArray();

        $filtered = collect($base)->reject(fn ($a) => in_array($a['code'], $removeCodes))->values();

        return $filtered->merge($additions)->sortBy('code')->values()->toArray();
    }

    private function baseAccounts(): array { /* ... general template array ... */ }
    private function serviceAccounts(): array { /* ... service overrides ... */ }
    private function retailAccounts(): array { /* ... retail overrides ... */ }
    private function creativeAccounts(): array { /* ... creative overrides ... */ }

    /**
     * Seed default expense categories mapped to the CoA expense accounts.
     */
    private function seedExpenseCategories(int $companyId): void
    {
        $categories = [
            ['name' => 'Office & Admin',              'color' => '#3B82F6', 'account_code' => '6300'],
            ['name' => 'Travel & Transportation',     'color' => '#8B5CF6', 'account_code' => '6800'],
            ['name' => 'Marketing & Advertising',     'color' => '#EC4899', 'account_code' => '6400'],
            ['name' => 'Rent & Utilities',             'color' => '#F59E0B', 'account_code' => '6100'],
            ['name' => 'Professional Services',        'color' => '#10B981', 'account_code' => '6700'],
            ['name' => 'Insurance',                    'color' => '#6366F1', 'account_code' => '6500'],
            ['name' => 'Meals & Entertainment',        'color' => '#F97316', 'account_code' => '6810'],
            ['name' => 'Software & Subscriptions',     'color' => '#14B8A6', 'account_code' => '6350'],
            ['name' => 'Equipment & Supplies',         'color' => '#64748B', 'account_code' => '6300'],
            ['name' => 'Bank & Financial Fees',        'color' => '#EF4444', 'account_code' => '7000'],
            ['name' => 'Other / Miscellaneous',        'color' => '#6B7280', 'account_code' => '7900'],
        ];

        foreach ($categories as $cat) {
            $account = Account::where('company_id', $companyId)
                ->where('code', $cat['account_code'])
                ->first();

            ExpenseCategory::create([
                'company_id'         => $companyId,
                'name'               => $cat['name'],
                'color'              => $cat['color'],
                'default_account_id' => $account?->id,
                'is_active'          => true,
            ]);
        }
    }
}
```

### 11.4 Activation Flow

When a user activates the accounting addon, they go through a quick **onboarding wizard** (1–2 steps):

#### Step 1: Controller — `AccountingActivationController`

```php
// app/Http/Controllers/Accounting/AccountingActivationController.php
namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Accounting\ChartOfAccountSeeder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountingActivationController extends Controller
{
    public function showSetup()
    {
        // Show template selection page
        return Inertia::render('Accounting/Setup', [
            'templates' => ChartOfAccountSeeder::getAvailableTemplates(),
        ]);
    }

    public function activate(Request $request, ChartOfAccountSeeder $seeder)
    {
        $request->validate([
            'template' => 'required|in:general,service,retail,creative',
        ]);

        $user = $request->user();
        $companyId = $user->company_id;

        // Seed the Chart of Accounts
        $count = $seeder->seed($companyId, $request->template);

        return redirect()
            ->route('accounting.dashboard')
            ->with('success', "Accounting module activated! {$count} accounts created from the \"{$request->template}\" template.");
    }
}
```

#### Step 2: Route

```php
// Inside the auth + verified_account group (but NOT behind accounting middleware,
// because the user hasn't activated yet)
Route::get('/accounting/setup', [Accounting\AccountingActivationController::class, 'showSetup'])
    ->name('accounting.setup');
Route::post('/accounting/activate', [Accounting\AccountingActivationController::class, 'activate'])
    ->name('accounting.activate');
```

#### Step 3: Vue Page — `Accounting/Setup.vue`

A beautiful onboarding page shown once when the user first activates the module:

```
┌──────────────────────────────────────────────────────────┐
│  🧾 Set Up Your Accounting                               │
│                                                          │
│  Choose a Chart of Accounts template that best           │
│  matches your business type. You can always add,         │
│  edit, or remove accounts later.                         │
│                                                          │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐ │
│  │  🏢       │  │  🛠️       │  │  🛒       │  │  🎨       │ │
│  │ General  │  │ Service  │  │ Retail   │  │ Creative │ │
│  │ Standard │  │ Business │  │ Trading  │  │ Freelanc │ │
│  │          │  │          │  │          │  │          │ │
│  │ ~50 accs │  │ ~55 accs │  │ ~60 accs │  │ ~48 accs │ │
│  │ ○ select │  │ ● select │  │ ○ select │  │ ○ select │ │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘ │
│                                                          │
│  ▸ Preview accounts in this template  (expandable)       │
│                                                          │
│                          [ Activate Accounting →]        │
└──────────────────────────────────────────────────────────┘
```

**Features of the setup page:**
- 4 template cards with icons, descriptions, and account counts
- Click a card to select it (highlighted border)
- Expandable preview showing all accounts in the selected template, grouped by type (Assets, Liabilities, etc.)
- One-click "Activate Accounting" button that triggers seeding
- After activation, redirects to the Accounting Dashboard

### 11.5 Updated Activation Middleware Logic

The `EnsureAccountingModule` middleware should also check if CoA has been seeded. If the addon is active but no accounts exist, redirect to the setup page:

```php
class EnsureAccountingModule
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$this->hasAccountingAddon($user)) {
            // Not subscribed
            if ($request->wantsJson() || $request->header('X-Inertia')) {
                abort(403, 'Accounting module is not active on your subscription.');
            }
            return redirect()->route('dashboard')
                ->with('error', 'Please subscribe to the Accounting addon to access this feature.');
        }

        // Subscribed but not yet set up — redirect to setup wizard
        if (!$this->hasBeenSetUp($user)) {
            return redirect()->route('accounting.setup');
        }

        return $next($request);
    }

    private function hasAccountingAddon($user): bool
    {
        return in_array('accounting', $user->addons ?? []);
    }

    private function hasBeenSetUp($user): bool
    {
        return \App\Models\Accounting\Account::where('company_id', $user->company_id)->exists();
    }
}
```

### 11.6 Summary of Auto-Seeding Additions

| Item | File / Location |
|---|---|
| **Service Class** | `app/Services/Accounting/ChartOfAccountSeeder.php` |
| **Activation Controller** | `app/Http/Controllers/Accounting/AccountingActivationController.php` |
| **Setup Vue Page** | `resources/js/Pages/Accounting/Setup.vue` |
| **Routes** | `accounting.setup` (GET) + `accounting.activate` (POST) |
| **Middleware Update** | `EnsureAccountingModule` — redirect to setup if not seeded |
| **Templates** | 4 built-in: `general`, `service`, `retail`, `creative` |
| **Expense Categories** | 11 default categories auto-seeded with color codes |

### 11.7 Updated Phase 1 Tasks

Add these to Phase 1 (Foundation):

| Task | Files |
|---|---|
| Create `ChartOfAccountSeeder` service | `app/Services/Accounting/ChartOfAccountSeeder.php` |
| Create `AccountingActivationController` | `app/Http/Controllers/Accounting/AccountingActivationController.php` |
| Create `Accounting/Setup.vue` | `resources/js/Pages/Accounting/Setup.vue` |
| Add setup + activate routes | `routes/web.php` |
| Update middleware to check for setup | `EnsureAccountingModule.php` |

---

## 12. Excel Import & Export

### 12.1 Design Philosophy

Many users prefer to work in Excel/Google Sheets — especially accountants and bookkeepers who maintain data offline or migrate from legacy systems. The accounting module should support:

1. **Downloadable template files** (`.xlsx`) for each importable data type
2. **Bulk import** from an uploaded spreadsheet with validation & error reporting
3. **Export** existing data to Excel for offline work or archival

### 12.2 Package Dependency

We'll use **[Laravel Excel (Maatwebsite)](https://laravel-excel.com/)** — the de facto standard for Excel in Laravel:

```bash
composer require maatwebsite/excel
```

> Supports `.xlsx`, `.xls`, `.csv`, and `.ods` formats.

### 12.3 Importable Data Types

| # | Data Type | Template File | Use Case |
|---|---|---|---|
| 1 | **Chart of Accounts** | `coa_template.xlsx` | Migrate existing CoA from another system |
| 2 | **Journal Entries** | `journal_entries_template.xlsx` | Bulk-enter historical journal entries |
| 3 | **Expenses** | `expenses_template.xlsx` | Import expense records from spreadsheets |
| 4 | **Bills** | `bills_template.xlsx` | Import vendor bills in bulk |
| 5 | **Bank Transactions** | `bank_transactions_template.xlsx` | Import bank statement data for reconciliation |

---

### 12.4 Template File Definitions

Each template `.xlsx` file ships with:
- **Header row** with exact column names
- **Example row(s)** with sample data (highlighted in light yellow)
- **Instructions sheet** explaining each column, required vs. optional, and accepted formats
- **Validation dropdowns** where applicable (e.g., account type, status)

---

#### 📄 Template 1: `coa_template.xlsx` — Chart of Accounts

| Column | Required | Format | Example | Notes |
|---|---|---|---|---|
| `code` | ✅ | String (max 20) | `1000` | Must be unique within company |
| `name` | ✅ | String | `Cash` | Account display name |
| `type` | ✅ | Dropdown | `asset` | One of: `asset`, `liability`, `equity`, `revenue`, `expense` |
| `subtype` | ✅ | Dropdown | `current_asset` | Must match the parent `type` (see mapping) |
| `parent_code` | | String | `1000` | Code of the parent account (for hierarchy) |
| `description` | | String | `Main cash account` | Optional note |
| `opening_balance` | | Number | `50000.00` | Starting balance for migration |

**Instructions Sheet Content:**
```
📋 Chart of Accounts Import Instructions
─────────────────────────────────────────
• Fill in one account per row starting from row 2
• The 'code' must be unique — duplicates will be rejected
• Valid type → subtype mappings:
    asset     → current_asset, fixed_asset, other_asset
    liability → current_liability, long_term_liability
    equity    → owner_equity, retained_earnings
    revenue   → operating_revenue, other_revenue
    expense   → operating_expense, cost_of_goods, other_expense
• 'parent_code' is optional — use it to create sub-accounts
• 'opening_balance' defaults to 0 if left blank
• Remove the yellow example rows before uploading
```

---

#### 📄 Template 2: `journal_entries_template.xlsx` — Journal Entries

| Column | Required | Format | Example | Notes |
|---|---|---|---|---|
| `entry_number` | ✅ | String | `JE-0001` | Must be unique; auto-generated if blank |
| `entry_date` | ✅ | Date | `2026-01-15` | `YYYY-MM-DD` or `DD/MM/YYYY` |
| `account_code` | ✅ | String | `1000` | Must exist in Chart of Accounts |
| `description` | | String | `Cash received from client` | Line-level description |
| `debit` | | Number | `5000.00` | Leave blank if this is a credit line |
| `credit` | | Number | | Leave blank if this is a debit line |
| `reference` | | String | `INV-2026-001` | External reference |

**How multi-line entries work:**
- Rows with the **same `entry_number`** are grouped into one journal entry
- Each row becomes a journal entry line
- Total debits must equal total credits per entry number — otherwise the group is rejected

**Example:**
| entry_number | entry_date | account_code | description | debit | credit | reference |
|---|---|---|---|---|---|---|
| JE-0001 | 2026-01-15 | 1000 | Cash received | 5000.00 | | INV-001 |
| JE-0001 | 2026-01-15 | 4000 | Sales revenue | | 5000.00 | INV-001 |
| JE-0002 | 2026-01-16 | 6100 | Office rent | 3000.00 | | |
| JE-0002 | 2026-01-16 | 1100 | Paid from bank | | 3000.00 | |

---

#### 📄 Template 3: `expenses_template.xlsx` — Expenses

| Column | Required | Format | Example | Notes |
|---|---|---|---|---|
| `expense_date` | ✅ | Date | `2026-01-20` | `YYYY-MM-DD` or `DD/MM/YYYY` |
| `category` | | String | `Office & Admin` | Must match existing expense category name |
| `account_code` | ✅ | String | `6300` | Expense account code from CoA |
| `payment_account_code` | | String | `1100` | Account the expense was paid from |
| `amount` | ✅ | Number | `250000.00` | Expense amount (before tax) |
| `tax_amount` | | Number | `25000.00` | Tax amount; defaults to 0 |
| `description` | | String | `Office supplies for January` | |
| `vendor` | | String | `PT. Stationery Jaya` | Matched to existing client/vendor name |
| `reference` | | String | `RCT-001` | Receipt or reference number |
| `status` | | Dropdown | `approved` | One of: `pending`, `approved`, `paid` |

---

#### 📄 Template 4: `bills_template.xlsx` — Bills (Accounts Payable)

| Column | Required | Format | Example | Notes |
|---|---|---|---|---|
| `bill_number` | ✅ | String | `BILL-001` | Must be unique |
| `vendor` | | String | `PT. Supplier ABC` | Matched to existing client name |
| `bill_date` | ✅ | Date | `2026-01-10` | |
| `due_date` | ✅ | Date | `2026-02-10` | |
| `account_code` | ✅ | String | `5000` | Expense/COGS account to debit |
| `description` | | String | `Raw materials purchase` | Line item description |
| `quantity` | | Number | `100` | Defaults to 1 |
| `unit_price` | ✅ | Number | `50000.00` | |
| `tax_amount` | | Number | `500000.00` | Tax for this line |

**How multi-line bills work:**
- Rows with the **same `bill_number`** are grouped into one bill
- Each row becomes a bill item
- Bill totals are auto-calculated from line items

---

#### 📄 Template 5: `bank_transactions_template.xlsx` — Bank Transactions

| Column | Required | Format | Example | Notes |
|---|---|---|---|---|
| `transaction_date` | ✅ | Date | `2026-01-15` | |
| `description` | ✅ | String | `Transfer from Client X` | |
| `amount` | ✅ | Number | `5000000.00` | Positive = deposit, negative = withdrawal |
| `reference` | | String | `TXN-12345` | Bank reference number |
| `running_balance` | | Number | `25000000.00` | Optional; for reconciliation reference |

> **Note:** The bank account to import into is selected on the import page, not in the spreadsheet.

---

### 12.5 Backend Implementation

#### Import Service: `AccountingImportService`

```php
// app/Services/Accounting/AccountingImportService.php
namespace App\Services\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use App\Models\Accounting\Expense;
use App\Models\Accounting\Bill;
use App\Models\Accounting\BillItem;
use App\Models\Accounting\BankTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AccountingImportService
{
    /**
     * Import results tracking.
     */
    protected int $imported = 0;
    protected int $skipped = 0;
    protected array $errors = [];

    /**
     * Import Chart of Accounts from parsed rows.
     */
    public function importAccounts(int $companyId, Collection $rows): array
    {
        // Validate each row
        // Skip header/example rows
        // Create accounts in order (parents first for hierarchy)
        // Track results
        return $this->results();
    }

    /**
     * Import Journal Entries from parsed rows (grouped by entry_number).
     */
    public function importJournalEntries(int $companyId, Collection $rows): array
    {
        // Group rows by entry_number
        // Validate debit == credit per group
        // Create JournalEntry + JournalEntryLines within transaction
        return $this->results();
    }

    /**
     * Import Expenses from parsed rows.
     */
    public function importExpenses(int $companyId, Collection $rows): array
    {
        // Validate account codes exist
        // Match category names to ExpenseCategory
        // Match vendor names to Client
        // Auto-generate expense numbers
        return $this->results();
    }

    /**
     * Import Bills from parsed rows (grouped by bill_number).
     */
    public function importBills(int $companyId, Collection $rows): array
    {
        // Group rows by bill_number
        // Create Bill + BillItems within transaction
        // Match vendor names to Client
        return $this->results();
    }

    /**
     * Import Bank Transactions from parsed rows.
     */
    public function importBankTransactions(int $companyId, int $bankAccountId, Collection $rows): array
    {
        // Validate and bulk-insert bank transactions
        // All marked as 'unmatched' initially
        return $this->results();
    }

    /**
     * Get import results summary.
     */
    private function results(): array
    {
        return [
            'imported' => $this->imported,
            'skipped'  => $this->skipped,
            'errors'   => $this->errors,
            'total'    => $this->imported + $this->skipped,
        ];
    }
}
```

#### Laravel Excel Import Classes

One import class per data type, using **Maatwebsite\Excel**:

```php
// app/Imports/Accounting/AccountsImport.php
namespace App\Imports\Accounting;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Collection;

class AccountsImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public Collection $rows;

    public function collection(Collection $rows): void
    {
        $this->rows = $rows;
    }
}
```

Similar classes:
- `AccountsImport.php`
- `JournalEntriesImport.php`
- `ExpensesImport.php`
- `BillsImport.php`
- `BankTransactionsImport.php`

#### Export Classes (Template Downloads)

```php
// app/Exports/Accounting/AccountsTemplateExport.php
namespace App\Exports\Accounting;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AccountsTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Template'     => new AccountsTemplateSheet(),
            'Instructions' => new AccountsInstructionsSheet(),
        ];
    }
}

class AccountsTemplateSheet implements FromArray, WithHeadings, WithStyles
{
    public function headings(): array
    {
        return ['code', 'name', 'type', 'subtype', 'parent_code', 'description', 'opening_balance'];
    }

    public function array(): array
    {
        // Example rows (highlighted in yellow via styles)
        return [
            ['1000', 'Cash', 'asset', 'current_asset', '', 'Main cash account', '50000.00'],
            ['1100', 'Bank Account', 'asset', 'current_asset', '', 'Primary bank', '1000000.00'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                   'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1F2937']]],
            2 => ['fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FEF3C7']]],
            3 => ['fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FEF3C7']]],
        ];
    }
}
```

Similar template exports for all 5 data types.

#### Controller: `ImportExportController`

```php
// app/Http/Controllers/Accounting/ImportExportController.php
namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Imports\Accounting\AccountsImport;
use App\Imports\Accounting\JournalEntriesImport;
use App\Imports\Accounting\ExpensesImport;
use App\Imports\Accounting\BillsImport;
use App\Imports\Accounting\BankTransactionsImport;
use App\Exports\Accounting\AccountsTemplateExport;
use App\Exports\Accounting\JournalEntriesTemplateExport;
use App\Exports\Accounting\ExpensesTemplateExport;
use App\Exports\Accounting\BillsTemplateExport;
use App\Exports\Accounting\BankTransactionsTemplateExport;
use App\Exports\Accounting\AccountsExport;
use App\Services\Accounting\AccountingImportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ImportExportController extends Controller
{
    // ─── Import Page ───────────────────────────────
    public function index()
    {
        return Inertia::render('Accounting/Import/Index', [
            'bankAccounts' => auth()->user()
                ->bankAccounts()
                ->select('id', 'bank_name', 'account_number')
                ->get(),
        ]);
    }

    // ─── Template Downloads ────────────────────────
    public function downloadTemplate(string $type)
    {
        return match ($type) {
            'accounts'          => Excel::download(new AccountsTemplateExport(), 'coa_template.xlsx'),
            'journal-entries'   => Excel::download(new JournalEntriesTemplateExport(), 'journal_entries_template.xlsx'),
            'expenses'          => Excel::download(new ExpensesTemplateExport(), 'expenses_template.xlsx'),
            'bills'             => Excel::download(new BillsTemplateExport(), 'bills_template.xlsx'),
            'bank-transactions' => Excel::download(new BankTransactionsTemplateExport(), 'bank_transactions_template.xlsx'),
            default             => abort(404),
        };
    }

    // ─── Data Export ───────────────────────────────
    public function export(string $type)
    {
        return match ($type) {
            'accounts' => Excel::download(
                new AccountsExport(auth()->user()->company_id),
                'chart_of_accounts_' . now()->format('Y-m-d') . '.xlsx'
            ),
            // ... other exports
            default => abort(404),
        };
    }

    // ─── Import Handlers ──────────────────────────
    public function importAccounts(Request $request, AccountingImportService $service)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:5120']);

        $import = new AccountsImport();
        Excel::import($import, $request->file('file'));

        $results = $service->importAccounts(
            auth()->user()->company_id,
            $import->rows
        );

        return back()->with('importResults', $results);
    }

    public function importJournalEntries(Request $request, AccountingImportService $service)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:5120']);

        $import = new JournalEntriesImport();
        Excel::import($import, $request->file('file'));

        $results = $service->importJournalEntries(
            auth()->user()->company_id,
            $import->rows
        );

        return back()->with('importResults', $results);
    }

    public function importExpenses(Request $request, AccountingImportService $service)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:5120']);

        $import = new ExpensesImport();
        Excel::import($import, $request->file('file'));

        $results = $service->importExpenses(
            auth()->user()->company_id,
            $import->rows
        );

        return back()->with('importResults', $results);
    }

    public function importBills(Request $request, AccountingImportService $service)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:5120']);

        $import = new BillsImport();
        Excel::import($import, $request->file('file'));

        $results = $service->importBills(
            auth()->user()->company_id,
            $import->rows
        );

        return back()->with('importResults', $results);
    }

    public function importBankTransactions(Request $request, AccountingImportService $service)
    {
        $request->validate([
            'file'            => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'bank_account_id' => 'required|exists:bank_accounts,id',
        ]);

        $import = new BankTransactionsImport();
        Excel::import($import, $request->file('file'));

        $results = $service->importBankTransactions(
            auth()->user()->company_id,
            $request->bank_account_id,
            $import->rows
        );

        return back()->with('importResults', $results);
    }
}
```

### 12.6 Routes

```php
// Inside the accounting middleware group

// Import & Export
Route::prefix('import-export')->name('import-export.')->group(function () {
    Route::get('/', [Accounting\ImportExportController::class, 'index'])
        ->name('index');

    // Template downloads (no auth needed beyond accounting middleware)
    Route::get('/template/{type}', [Accounting\ImportExportController::class, 'downloadTemplate'])
        ->name('template')
        ->where('type', 'accounts|journal-entries|expenses|bills|bank-transactions');

    // Data exports
    Route::get('/export/{type}', [Accounting\ImportExportController::class, 'export'])
        ->name('export')
        ->where('type', 'accounts|journal-entries|expenses|bills');

    // Import handlers
    Route::post('/import/accounts', [Accounting\ImportExportController::class, 'importAccounts'])
        ->name('import.accounts');
    Route::post('/import/journal-entries', [Accounting\ImportExportController::class, 'importJournalEntries'])
        ->name('import.journal-entries');
    Route::post('/import/expenses', [Accounting\ImportExportController::class, 'importExpenses'])
        ->name('import.expenses');
    Route::post('/import/bills', [Accounting\ImportExportController::class, 'importBills'])
        ->name('import.bills');
    Route::post('/import/bank-transactions', [Accounting\ImportExportController::class, 'importBankTransactions'])
        ->name('import.bank-transactions');
});
```

### 12.7 Vue Page: `Accounting/Import/Index.vue`

```
┌──────────────────────────────────────────────────────────────────────┐
│  📥 Import & Export Data                                             │
│                                                                      │
│  ┌─── Import Data ─────────────────────────────────────────────────┐ │
│  │                                                                  │ │
│  │  Select what you'd like to import:                               │ │
│  │                                                                  │ │
│  │  ┌────────────────┐  ┌────────────────┐  ┌────────────────┐     │ │
│  │  │ 📊 Chart of    │  │ 📝 Journal     │  │ 🧾 Expenses    │     │ │
│  │  │    Accounts    │  │    Entries     │  │                │     │ │
│  │  │                │  │                │  │                │     │ │
│  │  │ [⬇ Template]   │  │ [⬇ Template]   │  │ [⬇ Template]   │     │ │
│  │  └────────────────┘  └────────────────┘  └────────────────┘     │ │
│  │                                                                  │ │
│  │  ┌────────────────┐  ┌────────────────┐                         │ │
│  │  │ 📋 Bills       │  │ 🏦 Bank        │                         │ │
│  │  │                │  │    Transactions│                         │ │
│  │  │                │  │                │                         │ │
│  │  │ [⬇ Template]   │  │ [⬇ Template]   │                         │ │
│  │  └────────────────┘  └────────────────┘                         │ │
│  │                                                                  │ │
│  │  ──────────────────────────────────────────────────              │ │
│  │                                                                  │ │
│  │  1. Download the template file for the data type above           │ │
│  │  2. Fill in your data following the instructions inside          │ │
│  │  3. Upload the completed file below                              │ │
│  │                                                                  │ │
│  │  Import Type:  [▾ Chart of Accounts    ]                         │ │
│  │  Bank Account: [▾ BCA - 1234567890     ] ← only for bank txns   │ │
│  │                                                                  │ │
│  │  ┌──────────────────────────────────────┐                        │ │
│  │  │                                      │                        │ │
│  │  │   📎 Drag & drop your .xlsx file     │                        │ │
│  │  │      or click to browse              │                        │ │
│  │  │                                      │                        │ │
│  │  │   Supports: .xlsx, .xls, .csv        │                        │ │
│  │  │   Max size: 5MB                      │                        │ │
│  │  │                                      │                        │ │
│  │  └──────────────────────────────────────┘                        │ │
│  │                                                                  │ │
│  │                      [  Import Data →  ]                         │ │
│  │                                                                  │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                      │
│  ┌─── Import Results (shown after import) ─────────────────────────┐ │
│  │                                                                  │ │
│  │  ✅ 45 records imported successfully                              │ │
│  │  ⚠️  3 records skipped                                           │ │
│  │                                                                  │ │
│  │  Errors:                                                         │ │
│  │  ┌──────┬──────────────────────────────────────────┐             │ │
│  │  │ Row  │ Error                                    │             │ │
│  │  ├──────┼──────────────────────────────────────────┤             │ │
│  │  │ 5    │ Account code '1000' already exists       │             │ │
│  │  │ 12   │ Invalid type 'assets' (expected 'asset') │             │ │
│  │  │ 28   │ Debit/credit not balanced for JE-0005    │             │ │
│  │  └──────┴──────────────────────────────────────────┘             │ │
│  │                                                                  │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                      │
│  ┌─── Export Data ─────────────────────────────────────────────────┐ │
│  │                                                                  │ │
│  │  Download your existing data:                                    │ │
│  │                                                                  │ │
│  │  [⬇ Chart of Accounts] [⬇ Journal Entries] [⬇ Expenses] [⬇ Bills]│
│  │                                                                  │ │
│  └──────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────┘
```

### 12.8 Import on the Setup Page (Alternative to Templates)

The `Accounting/Setup.vue` page should also include an **"I have my own Chart of Accounts"** option alongside the 4 template cards:

```
┌──────────────────────────────────────────────────────────────────┐
│  🧾 Set Up Your Accounting                                       │
│                                                                  │
│  Choose a template OR import your own Chart of Accounts:         │
│                                                                  │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐            │
│  │ 🏢 Genrl │ │ 🛠 Srvce │ │ 🛒 Retl  │ │ 🎨 Cretv │            │
│  │  ~50     │ │  ~55     │ │  ~60     │ │  ~48     │            │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘            │
│                                                                  │
│  ── OR ────────────────────────────────────────                  │
│                                                                  │
│  ┌──────────────────────────────────────────────┐                │
│  │  📥 Import your own Chart of Accounts         │                │
│  │                                               │                │
│  │  Already have a CoA from another system?      │                │
│  │  Download our template, fill it in, and       │                │
│  │  upload it here.                              │                │
│  │                                               │                │
│  │  [⬇ Download Template]  [📎 Upload File]     │                │
│  └──────────────────────────────────────────────┘                │
│                                                                  │
│                         [ Activate Accounting → ]                │
└──────────────────────────────────────────────────────────────────┘
```

### 12.9 Validation Rules Summary

| Data Type | Key Validations |
|---|---|
| **Chart of Accounts** | Unique code per company; valid type/subtype combo; parent_code must exist if provided |
| **Journal Entries** | Debits == Credits per entry_number; account_code must exist; valid date format |
| **Expenses** | Account code must exist and be `expense` type; amount > 0; valid date |
| **Bills** | Unique bill_number; account_code exists; valid dates; unit_price > 0 |
| **Bank Transactions** | Valid date; amount ≠ 0; bank_account_id must belong to company |

### 12.10 File Structure Summary

```
app/
├── Imports/
│   └── Accounting/
│       ├── AccountsImport.php
│       ├── JournalEntriesImport.php
│       ├── ExpensesImport.php
│       ├── BillsImport.php
│       └── BankTransactionsImport.php
├── Exports/
│   └── Accounting/
│       ├── AccountsTemplateExport.php
│       ├── AccountsExport.php
│       ├── JournalEntriesTemplateExport.php
│       ├── ExpensesTemplateExport.php
│       ├── BillsTemplateExport.php
│       └── BankTransactionsTemplateExport.php
├── Http/
│   └── Controllers/
│       └── Accounting/
│           └── ImportExportController.php
├── Services/
│   └── Accounting/
│       └── AccountingImportService.php
resources/js/
├── Pages/
│   └── Accounting/
│       └── Import/
│           └── Index.vue
```

### 12.11 Navigation Update

Add to the Accounting sidebar:

```vue
<SidebarLink :href="route('accounting.import-export.index')" icon="mdi:file-import-outline">
    Import & Export
</SidebarLink>
```

### 12.12 Updated Phase Tasks

Add to **Phase 3** (Expenses & Bills):

| Task | Files |
|---|---|
| Install `maatwebsite/excel` | `composer.json` |
| Create Import classes (5 files) | `app/Imports/Accounting/` |
| Create Export/Template classes (6 files) | `app/Exports/Accounting/` |
| Create `AccountingImportService` | `app/Services/Accounting/AccountingImportService.php` |
| Create `ImportExportController` | `app/Http/Controllers/Accounting/ImportExportController.php` |
| Add import/export routes | `routes/web.php` |
| Create `Import/Index.vue` | `resources/js/Pages/Accounting/Import/Index.vue` |
| Update `Setup.vue` with import option | `resources/js/Pages/Accounting/Setup.vue` |
| Update sidebar navigation | `AppLayout.vue` |

### 12.13 Updated Summary Table

| Category | Count |
|---|---|
| **New Migrations** | 15 *(+3: currencies, audit_logs, alter bank_accounts)* |
| **New Models** | 13 *(+2: Currency, AuditLog)* |
| **New Controllers** | 11 *(+1 ImportExportController)* |
| **New Service Classes** | 7 *(+1 AccountingImportService, +2 AuditService, CurrencyService)* |
| **New Form Request Classes** | 11 |
| **New Middleware** | 1 |
| **New Import Classes** | 5 |
| **New Export Classes** | 6 |
| **New Route Endpoints** | ~45 *(+~10 import/export)* |
| **New Vue Pages** | ~22 *(+1 Import page + Setup update)* |
| **New Vue Components** | ~5 |
| **Existing Model Updates** | 4 (Invoice, BankAccount, Client, User) |
| **New Package** | `maatwebsite/excel` |
