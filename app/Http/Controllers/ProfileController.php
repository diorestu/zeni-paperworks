<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\SubscriptionInvoice;
use App\Services\PackageCatalogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function __construct(private readonly PackageCatalogService $packageCatalogService)
    {
    }

    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . ($user ? $user->id : 0)],
        ]);

        if ($user) {
            $user->update($validated);
        }

        return redirect()->back()->with('status', 'Profile updated');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('status', 'Password updated');
    }

    public function billing(): Response
    {
        $request = request();
        $user = $request->user();
        $currentPlan = $this->resolveActivePlanName($user);
        $pendingDowngrade = null;

        if ($user->pending_plan_name && $user->pending_plan_effective_at) {
            $pendingDowngrade = [
                'plan_name' => (string) $user->pending_plan_name,
                'effective_at' => Carbon::parse($user->pending_plan_effective_at)->toDateString(),
            ];
        }

        return Inertia::render('Profile/Billing', [
            'currentPlan' => $currentPlan,
            'currentPlanRenewsAt' => optional($user->plan_renews_at)->toDateString(),
            'pendingDowngrade' => $pendingDowngrade,
            'currentMonthInvoiceCount' => $this->currentMonthInvoiceCount($request),
            'packageCatalog' => $this->packageCatalogService->toRows(),
            'midtransEnabled' => filled(config('services.midtrans.server_key')) && filled(config('services.midtrans.client_key')),
            'paymentHistory' => $user->subscriptionInvoices()
                ->latest('invoice_date')
                ->get()
                ->map(fn ($invoice) => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'plan_name' => $invoice->plan_name,
                    'amount' => (float) $invoice->amount,
                    'invoice_date' => optional($invoice->invoice_date)->toDateString(),
                    'due_date' => optional($invoice->due_date)->toDateString(),
                    'status' => $invoice->status,
                    'receipt_url' => $invoice->status === 'paid'
                        ? route('settings.billing.receipts.download', ['invoice' => $invoice->id])
                        : null,
                ]),
        ]);
    }

    public function requestDowngrade(Request $request): RedirectResponse
    {
        $user = $request->user();
        $catalog = $this->packageCatalogService->all();
        $planSequence = array_keys($catalog);
        $validated = $request->validate([
            'plan' => ['required', 'string', Rule::in($planSequence)],
        ]);

        $currentPlan = $this->resolveActivePlanName($user);
        $targetPlan = (string) $validated['plan'];

        if ($currentPlan === 'Free') {
            return redirect()->back()->with('error', 'Akun Free tidak dapat melakukan downgrade.');
        }

        if ($targetPlan === $currentPlan) {
            return redirect()->back()->with('error', 'Paket target sama dengan paket saat ini.');
        }

        $currentRank = array_search($currentPlan, $planSequence, true);
        $targetRank = array_search($targetPlan, $planSequence, true);

        if ($currentRank === false || $targetRank === false || $targetRank >= $currentRank) {
            return redirect()->back()->with('error', 'Downgrade hanya bisa ke paket yang levelnya lebih rendah.');
        }

        if (! $user->plan_renews_at) {
            return redirect()->back()->with('error', 'Tanggal perpanjangan paket tidak ditemukan. Hubungi admin.');
        }

        $renewalDate = Carbon::parse($user->plan_renews_at)->startOfDay();
        if ($renewalDate->isPast()) {
            return redirect()->back()->with('error', 'Paket aktif sudah kedaluwarsa. Silakan pilih paket baru.');
        }

        $targetInvoiceLimit = $this->packageCatalogService->invoiceLimit($targetPlan);
        $invoiceCountThisMonth = $this->currentMonthInvoiceCount($request);

        if ($targetInvoiceLimit !== null && $invoiceCountThisMonth > $targetInvoiceLimit) {
            return redirect()->back()->with('error', "Tidak bisa downgrade ke {$targetPlan}. Invoice bulan ini {$invoiceCountThisMonth} melebihi limit paket {$targetPlan} ({$targetInvoiceLimit}).");
        }

        $user->update([
            'pending_plan_name' => $targetPlan,
            'pending_plan_effective_at' => $renewalDate->toDateString(),
        ]);

        $renewalInvoice = SubscriptionInvoice::query()
            ->where('user_id', $user->id)
            ->whereDate('billed_for_renewal_date', $renewalDate->toDateString())
            ->whereIn('status', ['draft', 'sent', 'overdue'])
            ->latest('id')
            ->first();

        if ($renewalInvoice) {
            $renewalInvoice->update([
                'plan_name' => $targetPlan,
                'amount' => (float) ($catalog[$targetPlan]['monthly_price'] ?? 0),
            ]);
        }

        return redirect()->back()->with('status', "Downgrade ke {$targetPlan} dijadwalkan pada {$renewalDate->format('d M Y')}.");
    }

    public function cancelDowngrade(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->pending_plan_name) {
            return redirect()->back()->with('error', 'Tidak ada downgrade terjadwal untuk dibatalkan.');
        }

        $user->update([
            'pending_plan_name' => null,
            'pending_plan_effective_at' => null,
        ]);

        return redirect()->back()->with('status', 'Downgrade terjadwal berhasil dibatalkan.');
    }

    public function billingCheckout(Request $request): Response
    {
        $plans = $this->packageCatalogService->all();
        $validated = $request->validate([
            'plan' => ['required', 'string', Rule::in(array_keys($plans))],
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $plan = $plans[$validated['plan']];
        $unitPrice = $validated['billing_cycle'] === 'yearly'
            ? (int) $plan['yearly_price']
            : (int) $plan['monthly_price'];
        $total = $validated['billing_cycle'] === 'yearly'
            ? $unitPrice * 12
            : $unitPrice;

        return Inertia::render('Profile/BillingCheckout', [
            'plan' => $validated['plan'],
            'billingCycle' => $validated['billing_cycle'],
            'unitPrice' => $unitPrice,
            'totalAmount' => $total,
            'midtransEnabled' => filled(config('services.midtrans.server_key')) && filled(config('services.midtrans.client_key')),
        ]);
    }

    public function billingSuccess(Request $request): Response
    {
        return Inertia::render('Profile/BillingSuccess', [
            'orderId' => (string) $request->query('order_id', ''),
            'status' => (string) $request->query('status', 'paid'),
        ]);
    }

    public function security(): Response
    {
        return Inertia::render('Profile/Security');
    }

    private function resolveActivePlanName($user): string
    {
        $planName = $user->plan_name ?? 'Free';

        if ($planName !== 'Free' && $user->plan_renews_at && Carbon::parse($user->plan_renews_at)->isPast()) {
            return 'Free';
        }

        return $planName;
    }

    private function currentMonthInvoiceCount(Request $request): int
    {
        return Invoice::query()
            ->where('company_id', $request->user()->company_id)
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->count();
    }
}
