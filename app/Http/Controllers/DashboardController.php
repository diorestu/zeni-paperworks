<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Invoice;
use App\Models\SubscriptionInvoice;
use App\Models\User;
use App\Services\PackageCatalogService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly PackageCatalogService $packageCatalogService)
    {
    }

    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user?->isSuperAdmin()) {
            return $this->superAdminDashboard();
        }

        if ($user && ! $user->wizard_completed) {
            return redirect()->route('onboarding.show');
        }

        $allowedPeriods = [3, 6, 12, 24];
        $periodMonths = (int) $request->integer('period', 12);
        if (! in_array($periodMonths, $allowedPeriods, true)) {
            $periodMonths = 12;
        }

        $now = Carbon::now();
        $start = $now->copy()->startOfMonth()->subMonths($periodMonths - 1);

        $monthlyCreated = Invoice::query()
            ->whereBetween('invoice_date', [$start->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->selectRaw('DATE_FORMAT(invoice_date, "%Y-%m") as ym, SUM(total) as total')
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $monthlyPaid = Invoice::query()
            ->where('status', 'paid')
            ->whereBetween('invoice_date', [$start->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->selectRaw('DATE_FORMAT(invoice_date, "%Y-%m") as ym, SUM(total) as total')
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $monthlyOverdue = Invoice::query()
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '<', $now->toDateString())
            ->whereBetween('due_date', [$start->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->selectRaw('DATE_FORMAT(due_date, "%Y-%m") as ym, SUM(total) as total')
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $labels = [];
        $createdSeries = [];
        $paidSeries = [];
        $overdueSeries = [];
        $cursor = $start->copy();

        for ($i = 0; $i < $periodMonths; $i++) {
            $ym = $cursor->format('Y-m');
            $labels[] = $cursor->format('M y');
            $createdSeries[] = (float) ($monthlyCreated[$ym] ?? 0);
            $paidSeries[] = (float) ($monthlyPaid[$ym] ?? 0);
            $overdueSeries[] = (float) ($monthlyOverdue[$ym] ?? 0);
            $cursor->addMonth();
        }

        $totalCreated = (float) Invoice::query()->sum('total');
        $totalPaid = (float) Invoice::query()->where('status', 'paid')->sum('total');
        $outstanding = (float) Invoice::query()->where('status', 'sent')->sum('total');
        $overdue = (float) Invoice::query()
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '<', $now->toDateString())
            ->sum('total');
        $unpaid = (float) Invoice::query()->whereIn('status', ['draft', 'sent'])->sum('total');

        $outstandingCount = Invoice::query()->where('status', 'sent')->count();
        $draftCount = Invoice::query()->where('status', 'draft')->count();
        $paidCount = Invoice::query()->where('status', 'paid')->count();
        $totalInvoicesCount = Invoice::query()->count();
        $overdueCount = Invoice::query()
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '<', $now->toDateString())
            ->count();
        $unpaidCount = Invoice::query()->whereIn('status', ['draft', 'sent'])->count();
        $averageInvoice = $totalInvoicesCount > 0 ? $totalCreated / $totalInvoicesCount : 0;
        $collectionRate = $totalCreated > 0 ? ($totalPaid / $totalCreated) * 100 : 0;

        return Inertia::render('Dashboard', [
            'range' => [
                'start' => $start->format('d M Y'),
                'end' => $now->format('d M Y'),
            ],
            'kpis' => [
                'total_created' => $totalCreated,
                'total_paid' => $totalPaid,
                'outstanding' => $outstanding,
                'overdue' => $overdue,
                'unpaid' => $unpaid,
                'outstanding_count' => $outstandingCount,
                'draft_count' => $draftCount,
                'paid_count' => $paidCount,
                'total_invoices_count' => $totalInvoicesCount,
                'overdue_count' => $overdueCount,
                'unpaid_count' => $unpaidCount,
                'average_invoice' => $averageInvoice,
                'collection_rate' => round($collectionRate, 2),
            ],
            'chart' => [
                'labels' => $labels,
                'created' => $createdSeries,
                'paid' => $paidSeries,
                'overdue' => $overdueSeries,
            ],
            'period' => [
                'selected' => $periodMonths,
                'options' => $allowedPeriods,
            ],
            'verification' => [
                'email_verified' => (bool) $user?->email_verified_at,
                'email' => $user?->email,
            ],
        ]);
    }

    private function superAdminDashboard(): Response
    {
        $nonSuperAdminUsers = User::query()->where('role', '!=', 'super_admin');

        $subscriptionBaseQuery = SubscriptionInvoice::query()->whereHas('user', function ($query) {
            $query->where('role', '!=', 'super_admin');
        });

        $planBreakdown = (clone $nonSuperAdminUsers)
            ->selectRaw("COALESCE(plan_name, 'Free') as plan_name, COUNT(*) as total")
            ->groupBy('plan_name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'plan_name' => $row->plan_name,
                'total' => (int) $row->total,
            ]);

        $users = (clone $nonSuperAdminUsers)
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'plan_name' => $user->plan_name ?? 'Free',
                'plan_renews_at' => optional($user->plan_renews_at)->toDateString(),
                'registered_at' => optional($user->created_at)->toDateString(),
                'email_verified_at' => optional($user->email_verified_at)->toDateTimeString(),
            ]);

        $subscriptionHistory = (clone $subscriptionBaseQuery)
            ->with('user:id,name,email')
            ->latest('invoice_date')
            ->limit(50)
            ->get()
            ->map(fn (SubscriptionInvoice $invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'user_name' => $invoice->user?->name,
                'user_email' => $invoice->user?->email,
                'plan_name' => $invoice->plan_name,
                'amount' => (float) $invoice->amount,
                'invoice_date' => optional($invoice->invoice_date)->toDateString(),
                'due_date' => optional($invoice->due_date)->toDateString(),
                'status' => $invoice->status,
                'auto_generated' => (bool) $invoice->auto_generated,
            ]);

        $feedbacks = Feedback::query()
            ->with('user:id,name,email')
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->map(fn (Feedback $feedback) => [
                'id' => $feedback->id,
                'name' => $feedback->name,
                'company' => $feedback->company,
                'role' => $feedback->role,
                'rating' => (int) $feedback->rating,
                'message' => $feedback->message,
                'created_at' => optional($feedback->created_at)->toDateTimeString(),
                'user_name' => $feedback->user?->name,
                'user_email' => $feedback->user?->email,
            ]);

        return Inertia::render('SuperAdmin/Dashboard', [
            'kpis' => [
                'registered_users' => (clone $nonSuperAdminUsers)->count(),
                'paid_plan_users' => (clone $nonSuperAdminUsers)->where('plan_name', '!=', 'Free')->count(),
                'total_revenue_paid' => (float) (clone $subscriptionBaseQuery)->where('status', 'paid')->sum('amount'),
                'total_revenue_invoiced' => (float) (clone $subscriptionBaseQuery)->whereIn('status', ['draft', 'sent', 'paid', 'overdue'])->sum('amount'),
                'subscription_invoices_count' => (clone $subscriptionBaseQuery)->count(),
                'feedback_count' => Feedback::query()->count(),
            ],
            'plans' => $planBreakdown,
            'packageCatalog' => $this->packageCatalogService->toRows(),
            'users' => $users,
            'subscriptionHistory' => $subscriptionHistory,
            'feedbacks' => $feedbacks,
        ]);
    }

    public function updatePackages(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $allowedPlans = array_keys($this->packageCatalogService->defaults());

        $validated = $request->validate([
            'packages' => ['required', 'array', 'min:1'],
            'packages.*.plan_name' => ['required', 'string', Rule::in($allowedPlans)],
            'packages.*.monthly_price' => ['required', 'integer', 'min:0'],
            'packages.*.yearly_price' => ['required', 'integer', 'min:0'],
            'packages.*.invoice_limit' => ['nullable', 'integer', 'min:1'],
        ]);

        $rows = collect($validated['packages'])
            ->keyBy('plan_name')
            ->map(fn (array $row) => [
                'plan_name' => $row['plan_name'],
                'monthly_price' => (int) $row['monthly_price'],
                'yearly_price' => (int) $row['yearly_price'],
                'invoice_limit' => $row['invoice_limit'] !== null ? (int) $row['invoice_limit'] : null,
            ])
            ->values()
            ->all();

        $this->packageCatalogService->upsertRows($rows);

        return redirect()->back()->with('status', 'Package pricing and limits updated successfully.');
    }

    public function verifyUser(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
        abort_if($user->isSuperAdmin(), 422, 'Cannot verify super admin account from this action.');

        if (!$user->email_verified_at) {
            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();
        }

        return redirect()->back()->with('status', 'User has been auto verified.');
    }
}
