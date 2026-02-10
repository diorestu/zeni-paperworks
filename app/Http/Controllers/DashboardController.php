<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
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
}
