<?php

namespace App\Console\Commands;

use App\Models\SubscriptionInvoice;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AutoBillRenewalInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:autobill-renewals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate subscription invoices exactly 14 days before plan expiry';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetRenewalDate = Carbon::today()->addDays(14);

        $users = User::query()
            ->whereNotNull('plan_renews_at')
            ->whereDate('plan_renews_at', $targetRenewalDate->toDateString())
            ->where('plan_name', '!=', 'Free')
            ->get();

        $created = 0;

        foreach ($users as $user) {
            $renewalDate = Carbon::parse($user->plan_renews_at);

            $alreadyBilled = SubscriptionInvoice::query()
                ->where('user_id', $user->id)
                ->whereDate('billed_for_renewal_date', $renewalDate->toDateString())
                ->exists();

            if ($alreadyBilled) {
                continue;
            }

            $amount = $this->getPlanAmount($user->plan_name);

            if ($amount <= 0) {
                continue;
            }

            SubscriptionInvoice::create([
                'user_id' => $user->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'plan_name' => $user->plan_name,
                'amount' => $amount,
                'period_start' => $renewalDate->copy()->subMonth(),
                'period_end' => $renewalDate,
                'invoice_date' => Carbon::today(),
                'due_date' => $renewalDate,
                'status' => 'sent',
                'auto_generated' => true,
                'billed_for_renewal_date' => $renewalDate,
            ]);

            $created++;
        }

        $this->info("Auto billing completed. Generated {$created} invoice(s).");

        return self::SUCCESS;
    }

    private function getPlanAmount(string $planName): float
    {
        return match ($planName) {
            'Basic' => 49000,
            'Pro' => 139000,
            'Enterprise' => 199000,
            default => 0,
        };
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'SUB';
        $dateCode = Carbon::now()->format('ymd');

        $lastInvoice = SubscriptionInvoice::query()
            ->where('invoice_number', 'like', "{$prefix}/{$dateCode}/%")
            ->orderByDesc('id')
            ->first();

        if ($lastInvoice) {
            $parts = explode('/', $lastInvoice->invoice_number);
            $lastSequence = (int) end($parts);
            $nextSequence = str_pad((string) ($lastSequence + 1), 3, '0', STR_PAD_LEFT);
        } else {
            $nextSequence = '001';
        }

        return "{$prefix}/{$dateCode}/{$nextSequence}";
    }
}
