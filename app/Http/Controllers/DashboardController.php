<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Invoice;
use App\Models\SubscriptionInvoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        if ($request->user()?->isSuperAdmin()) {
            return $this->superAdminDashboard();
        }

        $now = Carbon::now();
        $start = $now->copy()->startOfMonth()->subMonths(11);

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

        for ($i = 0; $i < 12; $i++) {
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
        $overdueCount = Invoice::query()
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '<', $now->toDateString())
            ->count();
        $unpaidCount = Invoice::query()->whereIn('status', ['draft', 'sent'])->count();

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
                'overdue_count' => $overdueCount,
                'unpaid_count' => $unpaidCount,
            ],
            'chart' => [
                'labels' => $labels,
                'created' => $createdSeries,
                'paid' => $paidSeries,
                'overdue' => $overdueSeries,
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
            'users' => $users,
            'subscriptionHistory' => $subscriptionHistory,
            'feedbacks' => $feedbacks,
        ]);
    }
}
