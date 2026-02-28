<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionExpiringSoonMail;
use App\Models\SubscriptionInvoice;
use App\Models\User;
use App\Services\PackageCatalogService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class AutoBillRenewalInvoices extends Command
{
    public function __construct(private readonly PackageCatalogService $packageCatalogService)
    {
        parent::__construct();
    }

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
        $today = Carbon::today();
        $this->applyDuePendingPlanChanges($today);

        $targetRenewalDate = $today->copy()->addDays(14);

        $users = User::query()
            ->whereNotNull('plan_renews_at')
            ->whereDate('plan_renews_at', $targetRenewalDate->toDateString())
            ->where('plan_name', '!=', 'Free')
            ->get();

        $created = 0;

        foreach ($users as $user) {
            $renewalDate = Carbon::parse($user->plan_renews_at)->startOfDay();
            $billingPlan = $this->resolveBillingPlanForRenewal($user, $renewalDate);

            $alreadyBilled = SubscriptionInvoice::query()
                ->where('user_id', $user->id)
                ->whereDate('billed_for_renewal_date', $renewalDate->toDateString())
                ->exists();

            if ($alreadyBilled) {
                continue;
            }

            $amount = $this->getPlanAmount($billingPlan);

            if ($amount <= 0) {
                continue;
            }

            $periodStart = $renewalDate->copy();
            $periodEnd = $renewalDate->copy()->addMonth();

            SubscriptionInvoice::create([
                'user_id' => $user->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'plan_name' => $billingPlan,
                'amount' => $amount,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'invoice_date' => $today,
                'due_date' => $renewalDate,
                'status' => 'sent',
                'auto_generated' => true,
                'billed_for_renewal_date' => $renewalDate,
            ]);

            if (!empty($user->email)) {
                Mail::to($user->email)->send(new SubscriptionExpiringSoonMail(
                    user: $user,
                    planName: $billingPlan,
                    renewalDate: $renewalDate->format('d M Y'),
                    amount: $amount
                ));
            }

            $created++;
        }

        $this->info("Auto billing completed. Generated {$created} invoice(s).");

        return self::SUCCESS;
    }

    private function getPlanAmount(string $planName): float
    {
        $package = $this->packageCatalogService->for($planName);

        return (float) ($package['monthly_price'] ?? 0);
    }

    private function resolveBillingPlanForRenewal(User $user, Carbon $renewalDate): string
    {
        if (! $user->pending_plan_name || ! $user->pending_plan_effective_at) {
            return (string) $user->plan_name;
        }

        $effectiveDate = Carbon::parse($user->pending_plan_effective_at)->startOfDay();
        if ($effectiveDate->equalTo($renewalDate->copy()->startOfDay())) {
            return (string) $user->pending_plan_name;
        }

        return (string) $user->plan_name;
    }

    private function applyDuePendingPlanChanges(Carbon $today): void
    {
        $dueUsers = User::query()
            ->whereNotNull('pending_plan_name')
            ->whereNotNull('pending_plan_effective_at')
            ->whereDate('pending_plan_effective_at', '<=', $today->toDateString())
            ->get();

        foreach ($dueUsers as $user) {
            $targetPlan = (string) $user->pending_plan_name;
            $nextRenewDate = null;

            if ($targetPlan !== 'Free') {
                $paidInvoice = SubscriptionInvoice::query()
                    ->where('user_id', $user->id)
                    ->where('status', 'paid')
                    ->where('plan_name', $targetPlan)
                    ->whereDate('period_start', '<=', $today->toDateString())
                    ->orderByDesc('period_end')
                    ->first();

                if (! $paidInvoice) {
                    $targetPlan = 'Free';
                } else {
                    $nextRenewDate = optional($paidInvoice->period_end)->toDateString();
                }
            }

            $user->update([
                'plan_name' => $targetPlan,
                'plan_renews_at' => $targetPlan === 'Free' ? null : $nextRenewDate,
                'pending_plan_name' => null,
                'pending_plan_effective_at' => null,
            ]);
        }
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
