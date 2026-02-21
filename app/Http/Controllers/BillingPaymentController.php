<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\AuditLog;
use App\Models\SubscriptionInvoice;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class BillingPaymentController extends Controller
{
    public function __construct(private readonly MidtransService $midtransService) {}

    public function createTransaction(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'plan' => ['required', Rule::in(array_keys($this->priceMap()))],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
        ]);

        $amount = $this->priceMap()[$validated['plan']][$validated['billing_cycle']];
        $startDate = Carbon::today();
        $endDate = $validated['billing_cycle'] === 'yearly'
            ? $startDate->copy()->addYear()
            : $startDate->copy()->addMonth();
        $callbacks = $this->callbacks();

        $invoice = SubscriptionInvoice::query()
            ->where('user_id', $user->id)
            ->whereDate('billed_for_renewal_date', $endDate->toDateString())
            ->where('plan_name', $validated['plan'])
            ->where('status', '!=', 'paid')
            ->latest('id')
            ->first();

        if (! $invoice) {
            $invoice = SubscriptionInvoice::create([
                'user_id' => $user->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'plan_name' => $validated['plan'],
                'amount' => $amount,
                'period_start' => $startDate,
                'period_end' => $endDate,
                'invoice_date' => $startDate,
                'due_date' => $endDate,
                'status' => 'sent',
                'auto_generated' => false,
                'billed_for_renewal_date' => $endDate,
            ]);
        } else {
            $invoice->update([
                'amount' => $amount,
                'period_start' => $startDate,
                'period_end' => $endDate,
                'invoice_date' => $startDate,
                'due_date' => $endDate,
                'status' => 'sent',
            ]);
        }

        $orderId = sprintf('SUB-%d-%s', $invoice->id, now()->format('YmdHisv'));

        $invoice->update([
            'payment_provider' => 'midtrans',
            'external_order_id' => $orderId,
            'payment_method' => null,
            'external_transaction_id' => null,
        ]);

        $snap = $this->midtransService->createSnapTransaction([
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $amount,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
            'item_details' => [
                [
                    'id' => strtolower($validated['plan']).'-'.$validated['billing_cycle'],
                    'price' => (int) $amount,
                    'quantity' => 1,
                    'name' => sprintf('%s Plan (%s)', $validated['plan'], ucfirst($validated['billing_cycle'])),
                ],
            ],
            'callbacks' => $callbacks,
        ]);

        $this->logAudit(
            invoice: $invoice,
            action: 'midtrans.checkout.created',
            oldValues: ['status' => $invoice->status],
            newValues: [
                'plan' => $validated['plan'],
                'billing_cycle' => $validated['billing_cycle'],
                'amount' => $amount,
                'external_order_id' => $orderId,
                'callbacks' => $callbacks,
            ],
            request: $request
        );

        return response()->json([
            'snap_token' => $snap['token'] ?? null,
            'redirect_url' => $snap['redirect_url'] ?? null,
            'order_id' => $orderId,
            'callbacks' => $callbacks,
        ]);
    }

    public function confirmTransaction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string'],
        ]);

        $invoice = SubscriptionInvoice::query()
            ->where('user_id', $request->user()->id)
            ->where('external_order_id', $validated['order_id'])
            ->firstOrFail();

        $statusPayload = $this->midtransService->getTransactionStatus($validated['order_id']);
        $previousStatus = $invoice->status;
        $invoice = $this->applyPaymentState($invoice, $statusPayload);

        $this->logAudit(
            invoice: $invoice,
            action: 'midtrans.confirm.checked',
            oldValues: ['status' => $previousStatus],
            newValues: [
                'status' => $invoice->status,
                'transaction_status' => $statusPayload['transaction_status'] ?? null,
                'external_order_id' => $validated['order_id'],
            ],
            request: $request
        );

        return response()->json([
            'status' => $invoice->status,
            'is_paid' => $invoice->status === 'paid',
        ]);
    }

    public function notification(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (! $this->midtransService->verifyNotificationSignature($payload)) {
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $invoice = SubscriptionInvoice::query()
            ->where('external_order_id', $payload['order_id'] ?? '')
            ->first();

        if (! $invoice) {
            return response()->json(['message' => 'Order not found.']);
        }

        $previousStatus = $invoice->status;
        $invoice = $this->applyPaymentState($invoice, $payload);

        $this->logAudit(
            invoice: $invoice,
            action: 'midtrans.webhook.received',
            oldValues: ['status' => $previousStatus],
            newValues: [
                'status' => $invoice->status,
                'transaction_status' => $payload['transaction_status'] ?? null,
                'payment_type' => $payload['payment_type'] ?? null,
                'external_order_id' => $payload['order_id'] ?? null,
                'external_transaction_id' => $payload['transaction_id'] ?? null,
            ],
            request: $request
        );

        return response()->json(['message' => 'OK']);
    }

    public function downloadReceipt(Request $request, SubscriptionInvoice $invoice): Response
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);
        abort_unless($invoice->status === 'paid', 422, 'Receipt hanya tersedia untuk pembayaran berhasil.');

        $pdf = Pdf::loadView('receipts.subscription', [
            'invoice' => $invoice->loadMissing('user'),
        ])->setPaper('a4');

        $filename = 'receipt-'.Str::slug($invoice->invoice_number).'.pdf';

        return $pdf->download($filename);
    }

    private function applyPaymentState(SubscriptionInvoice $invoice, array $payload): SubscriptionInvoice
    {
        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');
        $normalizedStatus = $this->mapInvoiceStatus($transactionStatus, $fraudStatus);

        if ($invoice->status === 'paid' && $normalizedStatus !== 'paid') {
            return $invoice->refresh();
        }

        $settlementTime = $payload['settlement_time'] ?? ($payload['transaction_time'] ?? null);

        $invoice->update([
            'status' => $normalizedStatus,
            'payment_provider' => 'midtrans',
            'payment_method' => $payload['payment_type'] ?? $invoice->payment_method,
            'external_transaction_id' => $payload['transaction_id'] ?? $invoice->external_transaction_id,
            'paid_at' => $normalizedStatus === 'paid'
                ? ($settlementTime ? Carbon::parse($settlementTime) : now())
                : null,
            'payment_payload' => $payload,
        ]);

        if ($normalizedStatus === 'paid') {
            $invoice->user()->update([
                'plan_name' => $invoice->plan_name,
                'plan_renews_at' => $invoice->period_end,
            ]);
        }

        return $invoice->refresh();
    }

    private function mapInvoiceStatus(string $transactionStatus, string $fraudStatus): string
    {
        return match ($transactionStatus) {
            'capture' => $fraudStatus === 'accept' ? 'paid' : 'sent',
            'settlement' => 'paid',
            'pending' => 'sent',
            'expire' => 'overdue',
            'cancel', 'deny', 'failure' => 'cancelled',
            default => 'sent',
        };
    }

    private function priceMap(): array
    {
        return [
            'Basic' => [
                'monthly' => 39000,
                'yearly' => 30000 * 12,
            ],
            'Pro' => [
                'monthly' => 99000,
                'yearly' => 75000 * 12,
            ],
            'Enterprise' => [
                'monthly' => 249000,
                'yearly' => 189000 * 12,
            ],
        ];
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'SUB';
        $dateCode = now()->format('ymd');

        $lastInvoice = SubscriptionInvoice::query()
            ->where('invoice_number', 'like', "{$prefix}/{$dateCode}/%")
            ->orderByDesc('id')
            ->first();

        if (! $lastInvoice) {
            return "{$prefix}/{$dateCode}/001";
        }

        $parts = explode('/', $lastInvoice->invoice_number);
        $lastSequence = (int) end($parts);
        $nextSequence = str_pad((string) ($lastSequence + 1), 3, '0', STR_PAD_LEFT);

        return "{$prefix}/{$dateCode}/{$nextSequence}";
    }

    private function callbacks(): array
    {
        return [
            'finish' => route('settings.billing', ['payment' => 'finish']),
            'unfinish' => route('settings.billing', ['payment' => 'unfinish']),
            'error' => route('settings.billing', ['payment' => 'error']),
        ];
    }

    private function logAudit(
        SubscriptionInvoice $invoice,
        string $action,
        array $oldValues,
        array $newValues,
        Request $request
    ): void {
        try {
            AuditLog::query()->create([
                'company_id' => $invoice->user->company_id ?? $invoice->user_id,
                'user_id' => $request->user()?->id,
                'auditable_type' => SubscriptionInvoice::class,
                'auditable_id' => $invoice->id,
                'action' => $action,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Failed to write payment audit log.', [
                'action' => $action,
                'invoice_id' => $invoice->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
