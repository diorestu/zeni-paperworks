<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Quotation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->company_id) {
            return response()->json([
                'notifications' => [],
                'unread_count' => 0,
                'last_seen_at' => $user?->notification_last_seen_at,
                'server_time' => now()->toIso8601String(),
            ]);
        }

        $notifications = collect();

        Invoice::query()
            ->with('client:id,name')
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->each(function (Invoice $invoice) use ($notifications) {
                $notifications->push([
                    'id' => 'invoice-created-' . $invoice->id,
                    'type' => 'invoice_created',
                    'icon' => 'si:ballot-line',
                    'title' => 'Invoice baru dibuat',
                    'message' => sprintf('%s untuk %s', $invoice->invoice_number, $invoice->client?->name ?? 'Client'),
                    'href' => route('invoices.show', $invoice),
                    'occurred_at' => optional($invoice->created_at)->toIso8601String(),
                ]);
            });

        Invoice::query()
            ->with('client:id,name')
            ->where('status', 'paid')
            ->whereColumn('updated_at', '>', 'created_at')
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->each(function (Invoice $invoice) use ($notifications) {
                $notifications->push([
                    'id' => 'invoice-paid-' . $invoice->id . '-' . optional($invoice->updated_at)->timestamp,
                    'type' => 'invoice_paid',
                    'icon' => 'si:check-line',
                    'title' => 'Pembayaran diterima',
                    'message' => sprintf('%s sudah dibayar', $invoice->invoice_number),
                    'href' => route('invoices.show', $invoice),
                    'occurred_at' => optional($invoice->updated_at)->toIso8601String(),
                ]);
            });

        Quotation::query()
            ->with('client:id,name')
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->each(function (Quotation $quotation) use ($notifications) {
                $notifications->push([
                    'id' => 'quotation-created-' . $quotation->id,
                    'type' => 'quotation_created',
                    'icon' => 'si:assignment-line',
                    'title' => 'Quotation baru dibuat',
                    'message' => sprintf('%s untuk %s', $quotation->quotation_number, $quotation->client?->name ?? 'Client'),
                    'href' => route('quotations.show', $quotation),
                    'occurred_at' => optional($quotation->created_at)->toIso8601String(),
                ]);
            });

        Client::query()
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->each(function (Client $client) use ($notifications) {
                $notifications->push([
                    'id' => 'client-created-' . $client->id,
                    'type' => 'client_created',
                    'icon' => 'si:user-line',
                    'title' => 'Client baru ditambahkan',
                    'message' => $client->name,
                    'href' => route('clients.index'),
                    'occurred_at' => optional($client->created_at)->toIso8601String(),
                ]);
            });

        Product::query()
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->each(function (Product $product) use ($notifications) {
                $notifications->push([
                    'id' => 'product-created-' . $product->id,
                    'type' => 'product_created',
                    'icon' => 'si:inventory-line',
                    'title' => 'Produk baru ditambahkan',
                    'message' => $product->name,
                    'href' => route('products.index'),
                    'occurred_at' => optional($product->created_at)->toIso8601String(),
                ]);
            });

        $since = null;
        if ($request->filled('since')) {
            try {
                $since = Carbon::parse((string) $request->query('since'));
            } catch (\Throwable $exception) {
                $since = null;
            }
        }

        $lastSeenAt = $user->notification_last_seen_at;

        $sorted = $notifications
            ->filter(fn (array $item) => !empty($item['occurred_at']))
            ->sortByDesc('occurred_at')
            ->values();

        if ($since) {
            $sorted = $sorted
                ->filter(fn (array $item) => Carbon::parse($item['occurred_at'])->greaterThan($since))
                ->values();
        }

        $unreadCount = $notifications
            ->filter(fn (array $item) => !empty($item['occurred_at']))
            ->filter(function (array $item) use ($lastSeenAt) {
                if (!$lastSeenAt) {
                    return true;
                }

                return Carbon::parse($item['occurred_at'])->greaterThan($lastSeenAt);
            })
            ->count();

        return response()->json([
            'notifications' => $sorted->take(15)->values(),
            'unread_count' => $unreadCount,
            'last_seen_at' => optional($lastSeenAt)->toIso8601String(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'notification_last_seen_at' => now(),
        ])->save();

        return response()->json([
            'success' => true,
            'last_seen_at' => optional($user->notification_last_seen_at)->toIso8601String(),
        ]);
    }
}
