<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::query()->with('client')->latest('invoice_date');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($clientQ) => $clientQ->where('name', 'like', "%{$search}%"));
            });
        }

        return response()->json([
            'data' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json([
            'data' => $invoice->load(['client', 'bankAccount', 'items.product', 'parentInvoice', 'continuationInvoices']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'bank_account_id' => [
                'nullable',
                Rule::exists('bank_accounts', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'is_down_payment' => ['nullable', 'boolean'],
            'parent_invoice_id' => [
                'nullable',
                Rule::exists('invoices', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'invoice_number' => [
                'nullable',
                'string',
                Rule::unique('invoices', 'invoice_number')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'nullable',
                Rule::exists('products', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $invoice = DB::transaction(function () use ($validated) {
            $subtotal = collect($validated['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price']);
            $taxTotal = 0;
            $total = $subtotal + $taxTotal;

            $invoice = Invoice::query()->create([
                'client_id' => $validated['client_id'],
                'bank_account_id' => $validated['bank_account_id'] ?? null,
                'is_down_payment' => (bool) ($validated['is_down_payment'] ?? false),
                'parent_invoice_id' => $validated['parent_invoice_id'] ?? null,
                'invoice_number' => $validated['invoice_number'] ?? $this->generateInvoiceNumber(),
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'status' => 'draft',
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $total,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $invoice->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return $invoice;
        });

        return response()->json([
            'message' => 'Invoice created.',
            'data' => $invoice->load(['client', 'bankAccount', 'items.product']),
        ], 201);
    }

    public function updateStatus(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['draft', 'sent', 'paid', 'overdue', 'cancelled'])],
        ]);

        $invoice->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Invoice status updated.',
            'data' => $invoice,
        ]);
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = Setting::where('key', 'invoice_prefix')->value('value') ?? 'INV';
        $dateCode = Carbon::now()->format('ymd');

        $lastInvoice = Invoice::query()
            ->where('invoice_number', 'like', "{$prefix}/{$dateCode}/%")
            ->orderByDesc('id')
            ->first();

        if (! $lastInvoice) {
            return "{$prefix}/{$dateCode}/001";
        }

        $parts = explode('/', $lastInvoice->invoice_number);
        $nextSequence = str_pad((string) (((int) end($parts)) + 1), 3, '0', STR_PAD_LEFT);

        return "{$prefix}/{$dateCode}/{$nextSequence}";
    }
}
