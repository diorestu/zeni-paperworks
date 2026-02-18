<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Setting;
use App\Models\SubscriptionInvoice;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $companyId = 1;
        $today = Carbon::today();

        User::updateOrCreate([
            'email' => 'super@paperwork.com',
        ], [
            'company_id' => $companyId,
            'name' => 'Super Admin',
            'email_verified_at' => now(),
            'password' => 'password',
            'role' => 'super_admin',
            'wizard_completed' => true,
            'plan_name' => 'Free',
            'plan_renews_at' => null,
        ]);

        $admin = User::updateOrCreate([
            'email' => 'admin@paperwork.com',
        ], [
            'company_id' => $companyId,
            'name' => 'Admin User',
            'email_verified_at' => now(),
            'password' => 'password',
            'role' => 'admin',
            'wizard_completed' => true,
            'plan_name' => 'Basic',
            'plan_renews_at' => $today->copy()->addDays(14)->toDateString(),
        ]);

        $regularUser = User::updateOrCreate([
            'email' => 'user@paperwork.com',
        ], [
            'company_id' => $companyId,
            'name' => 'Regular User',
            'email_verified_at' => now(),
            'password' => 'password',
            'role' => 'user',
            'wizard_completed' => true,
            'plan_name' => 'Pro',
            'plan_renews_at' => $today->copy()->addDays(14)->toDateString(),
        ]);

        $bankAccount = BankAccount::updateOrCreate([
            'user_id' => $admin->id,
            'account_number' => '1234567890',
        ], [
            'company_id' => $companyId,
            'bank_name' => 'Bank Central Asia',
            'account_name' => 'Paperwork Indonesia',
            'is_default' => true,
        ]);

        $clientPrimary = Client::updateOrCreate([
            'company_id' => $companyId,
            'name' => 'PT Maju Bersama',
        ], [
            'email' => 'finance@majubersama.co.id',
            'phone' => '081200001111',
            'company' => 'PT Maju Bersama',
            'industry_sector' => 'Retail',
            'address' => 'Jl. Sudirman No. 10, Jakarta',
        ]);

        $clientSecondary = Client::updateOrCreate([
            'company_id' => $companyId,
            'name' => 'CV Sinar Digital',
        ], [
            'email' => 'accounting@sinardigital.id',
            'phone' => '081233334444',
            'company' => 'CV Sinar Digital',
            'industry_sector' => 'Technology',
            'address' => 'Jl. Imam Bonjol No. 22, Bandung',
        ]);

        $productWebsite = Product::updateOrCreate([
            'company_id' => $companyId,
            'sku' => 'SRV-WEB-001',
        ], [
            'name' => 'Website Development',
            'price' => 150000.00,
            'description' => 'Custom company profile website development service.',
        ]);

        $productSupport = Product::updateOrCreate([
            'company_id' => $companyId,
            'sku' => 'SRV-SUP-001',
        ], [
            'name' => 'Technical Support',
            'price' => 75000.00,
            'description' => 'Monthly support and maintenance.',
        ]);

        Tax::updateOrCreate([
            'company_id' => $companyId,
            'name' => 'PPN',
        ], [
            'type' => 'add',
            'rate' => 11.00,
            'is_active' => true,
        ]);

        Tax::updateOrCreate([
            'company_id' => $companyId,
            'name' => 'PPh 23',
        ], [
            'type' => 'subtract',
            'rate' => 2.00,
            'is_active' => true,
        ]);

        $settings = [
            'invoice_prefix' => 'INV',
            'quotation_prefix' => 'QUO',
            'currency' => 'IDR',
            'company_name' => 'Paperwork Indonesia',
            'company_address' => 'Jl. Gatot Subroto No. 88, Jakarta',
            'company_phone' => '+62 21 5555 0000',
            'company_email' => 'hello@paperwork.com',
            'company_website' => 'https://paperwork.com',
            'company_tax_id' => '01.234.567.8-901.000',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate([
                'company_id' => $companyId,
                'key' => $key,
            ], [
                'value' => $value,
            ]);
        }

        $invoiceNumber = 'INV/'.$today->format('ymd').'/001';
        $invoiceSubtotal = 550000.00;
        $invoiceTax = 60500.00;

        $invoice = Invoice::updateOrCreate([
            'company_id' => $companyId,
            'invoice_number' => $invoiceNumber,
        ], [
            'client_id' => $clientPrimary->id,
            'bank_account_id' => $bankAccount->id,
            'invoice_date' => $today->toDateString(),
            'due_date' => $today->copy()->addDays(14)->toDateString(),
            'status' => 'sent',
            'subtotal' => $invoiceSubtotal,
            'tax_total' => $invoiceTax,
            'total' => $invoiceSubtotal + $invoiceTax,
            'notes' => 'Dummy invoice for initial seed.',
        ]);

        $invoice->items()->delete();
        $invoice->items()->createMany([
            [
                'product_id' => $productWebsite->id,
                'description' => 'Landing page and company profile website.',
                'quantity' => 2,
                'unit_price' => 150000.00,
                'subtotal' => 300000.00,
            ],
            [
                'product_id' => $productSupport->id,
                'description' => 'Deployment and one month support.',
                'quantity' => 1,
                'unit_price' => 250000.00,
                'subtotal' => 250000.00,
            ],
        ]);

        $quotationNumber = 'QUO/'.$today->format('ymd').'/001';
        $quotationSubtotal = 300000.00;
        $quotationTax = 33000.00;

        $quotation = Quotation::updateOrCreate([
            'company_id' => $companyId,
            'quotation_number' => $quotationNumber,
        ], [
            'client_id' => $clientSecondary->id,
            'quotation_date' => $today->toDateString(),
            'valid_until' => $today->copy()->addDays(30)->toDateString(),
            'status' => 'accepted',
            'subtotal' => $quotationSubtotal,
            'tax_total' => $quotationTax,
            'total' => $quotationSubtotal + $quotationTax,
            'notes' => 'Dummy quotation for initial seed.',
            'invoice_id' => $invoice->id,
        ]);

        $quotation->items()->delete();
        $quotation->items()->createMany([
            [
                'product_id' => $productWebsite->id,
                'description' => 'Website revamp package.',
                'quantity' => 1,
                'unit_price' => 150000.00,
                'subtotal' => 150000.00,
            ],
            [
                'product_id' => $productSupport->id,
                'description' => 'Maintenance and reporting package.',
                'quantity' => 2,
                'unit_price' => 75000.00,
                'subtotal' => 150000.00,
            ],
        ]);

        $renewalDate = Carbon::parse($regularUser->plan_renews_at);

        SubscriptionInvoice::updateOrCreate([
            'user_id' => $regularUser->id,
            'billed_for_renewal_date' => $regularUser->plan_renews_at,
        ], [
            'invoice_number' => 'SUB/'.$today->format('ymd').'/001',
            'plan_name' => $regularUser->plan_name,
            'amount' => 99000.00,
            'period_start' => $renewalDate->copy()->subMonth()->toDateString(),
            'period_end' => $renewalDate->toDateString(),
            'invoice_date' => $today->toDateString(),
            'due_date' => $renewalDate->toDateString(),
            'status' => 'sent',
            'paid_at' => null,
            'auto_generated' => true,
        ]);

        DB::table('password_reset_tokens')->updateOrInsert([
            'email' => $regularUser->email,
        ], [
            'token' => hash('sha256', 'dummy-reset-token'),
            'created_at' => now(),
        ]);

        DB::table('sessions')->updateOrInsert([
            'id' => 'seed-session-'.$regularUser->id,
        ], [
            'user_id' => $regularUser->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Seeder Dummy Session',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);

        DB::table('cache')->updateOrInsert([
            'key' => 'seed:app:name',
        ], [
            'value' => serialize('Paperwork'),
            'expiration' => now()->addDay()->timestamp,
        ]);

        DB::table('cache_locks')->updateOrInsert([
            'key' => 'seed:lock:bootstrap',
        ], [
            'owner' => 'database-seeder',
            'expiration' => now()->addMinutes(10)->timestamp,
        ]);

        if (! DB::table('jobs')->where('queue', 'seed_dummy')->exists()) {
            DB::table('jobs')->insert([
                'queue' => 'seed_dummy',
                'payload' => json_encode(['displayName' => 'SeedDummyJob']),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => now()->addYears(10)->timestamp,
                'created_at' => now()->timestamp,
            ]);
        }

        if (! DB::table('job_batches')->where('name', 'seed-dummy-batch')->exists()) {
            DB::table('job_batches')->insert([
                'id' => (string) Str::uuid(),
                'name' => 'seed-dummy-batch',
                'total_jobs' => 1,
                'pending_jobs' => 0,
                'failed_jobs' => 0,
                'failed_job_ids' => '[]',
                'options' => null,
                'cancelled_at' => null,
                'created_at' => now()->timestamp,
                'finished_at' => now()->timestamp,
            ]);
        }

        DB::table('failed_jobs')->updateOrInsert([
            'uuid' => '00000000-0000-0000-0000-000000000001',
        ], [
            'connection' => 'database',
            'queue' => 'seed_dummy',
            'payload' => json_encode(['displayName' => 'SeedDummyJob']),
            'exception' => 'Seeded dummy failed job entry.',
            'failed_at' => now(),
        ]);
    }
}
