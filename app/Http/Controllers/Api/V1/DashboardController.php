<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $totalCreated = (float) Invoice::query()->sum('total');
        $totalPaid = (float) Invoice::query()->where('status', 'paid')->sum('total');
        $outstanding = (float) Invoice::query()->where('status', 'sent')->sum('total');
        $overdue = (float) Invoice::query()->where('status', 'overdue')->sum('total');
        $draft = (float) Invoice::query()->where('status', 'draft')->sum('total');

        return response()->json([
            'summary' => [
                'total_created' => $totalCreated,
                'total_paid' => $totalPaid,
                'outstanding' => $outstanding,
                'overdue' => $overdue,
                'draft' => $draft,
                'invoice_count' => Invoice::query()->count(),
                'paid_count' => Invoice::query()->where('status', 'paid')->count(),
            ],
        ]);
    }
}
