<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Setting;
use App\Models\Tax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuotationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Quotation::query()->with('client')->latest('quotation_date');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('quotation_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($clientQ) => $clientQ->where('name', 'like', "%{$search}%"));
            });
        }

        return response()->json([
            'data' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function show(Quotation $quotation): JsonResponse
    {
        return response()->json([
            'data' => $quotation->load(['client', 'items.product', 'invoice']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'client_id' => ['required', Rule::exists('clients', 'id')->where(fn ($q) => $q->where('company_id', $companyId))],
            'quotation_number' => [
                'nullable',
                'string',
                Rule::unique('quotations', 'quotation_number')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'quotation_date' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:quotation_date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', Rule::exists('products', 'id')->where(fn ($q) => $q->where('company_id', $companyId))],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'selected_tax_ids' => ['nullable', 'array'],
            'selected_tax_ids.*' => ['integer', Rule::exists('taxes', 'id')->where(fn ($q) => $q->where('company_id', $companyId))],
        ]);

        $quotation = DB::transaction(function () use ($validated) {
            $totals = $this->buildTotals($validated['items'], $validated['selected_tax_ids'] ?? []);

            $quotation = Quotation::query()->create([
                'client_id' => $validated['client_id'],
                'quotation_number' => $validated['quotation_number'] ?? $this->generateQuotationNumber(),
                'quotation_date' => $validated['quotation_date'],
                'valid_until' => $validated['valid_until'],
                'notes' => $validated['notes'] ?? null,
                'subtotal' => $totals['subtotal'],
                'tax_total' => $totals['tax_total'],
                'total' => $totals['total'],
                'applied_taxes' => $totals['applied_taxes'],
                'status' => 'draft',
            ]);

            foreach ($validated['items'] as $item) {
                $quotation->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return $quotation;
        });

        return response()->json([
            'message' => 'Quotation created.',
            'data' => $quotation->load(['client', 'items.product']),
        ], 201);
    }

    public function update(Request $request, Quotation $quotation): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'client_id' => ['required', Rule::exists('clients', 'id')->where(fn ($q) => $q->where('company_id', $companyId))],
            'quotation_number' => [
                'required',
                'string',
                Rule::unique('quotations', 'quotation_number')->ignore($quotation->id)->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'quotation_date' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:quotation_date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', Rule::exists('products', 'id')->where(fn ($q) => $q->where('company_id', $companyId))],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'selected_tax_ids' => ['nullable', 'array'],
            'selected_tax_ids.*' => ['integer', Rule::exists('taxes', 'id')->where(fn ($q) => $q->where('company_id', $companyId))],
        ]);

        DB::transaction(function () use ($validated, $quotation) {
            $totals = $this->buildTotals($validated['items'], $validated['selected_tax_ids'] ?? []);

            $quotation->update([
                'client_id' => $validated['client_id'],
                'quotation_number' => $validated['quotation_number'],
                'quotation_date' => $validated['quotation_date'],
                'valid_until' => $validated['valid_until'],
                'notes' => $validated['notes'] ?? null,
                'subtotal' => $totals['subtotal'],
                'tax_total' => $totals['tax_total'],
                'total' => $totals['total'],
                'applied_taxes' => $totals['applied_taxes'],
            ]);

            $quotation->items()->delete();
            foreach ($validated['items'] as $item) {
                $quotation->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }
        });

        return response()->json([
            'message' => 'Quotation updated.',
            'data' => $quotation->fresh()->load(['client', 'items.product', 'invoice']),
        ]);
    }

    public function convert(Request $request, Quotation $quotation): JsonResponse
    {
        if ($quotation->invoice_id) {
            return response()->json([
                'message' => 'This quotation is already converted to invoice.',
            ], 422);
        }

        $invoice = DB::transaction(function () use ($quotation) {
            $invoice = Invoice::query()->create([
                'client_id' => $quotation->client_id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_date' => now()->toDateString(),
                'due_date' => $quotation->valid_until,
                'subtotal' => $quotation->subtotal,
                'tax_total' => $quotation->tax_total,
                'total' => $quotation->total,
                'status' => 'draft',
                'notes' => $quotation->notes,
            ]);

            foreach ($quotation->items as $item) {
                $invoice->items()->create([
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ]);
            }

            $quotation->update([
                'status' => 'accepted',
                'invoice_id' => $invoice->id,
            ]);

            return $invoice;
        });

        return response()->json([
            'message' => 'Quotation converted to invoice.',
            'data' => $invoice->load(['client', 'items.product']),
        ]);
    }

    private function buildTotals(array $items, array $selectedTaxIds): array
    {
        $subtotal = collect($items)->sum(fn ($item) => $item['quantity'] * $item['unit_price']);

        $taxes = Tax::query()
            ->whereIn('id', $selectedTaxIds)
            ->get(['id', 'name', 'type', 'rate']);

        $taxTotal = 0;
        $appliedTaxes = $taxes->map(function ($tax) use (&$taxTotal, $subtotal) {
            $amount = round(($subtotal * (float) $tax->rate) / 100, 2);
            $signedAmount = $tax->type === 'add' ? $amount : -$amount;
            $taxTotal += $signedAmount;

            return [
                'id' => $tax->id,
                'name' => $tax->name,
                'type' => $tax->type,
                'rate' => (float) $tax->rate,
                'amount' => $signedAmount,
            ];
        })->values()->all();

        return [
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $subtotal + $taxTotal,
            'applied_taxes' => $appliedTaxes,
        ];
    }

    private function generateQuotationNumber(): string
    {
        $prefix = Setting::where('key', 'quotation_prefix')->value('value') ?? 'QUO';
        $dateCode = Carbon::now()->format('ymd');

        $lastQuotation = Quotation::query()
            ->where('quotation_number', 'like', "{$prefix}/{$dateCode}/%")
            ->orderByDesc('id')
            ->first();

        if (! $lastQuotation) {
            return "{$prefix}/{$dateCode}/001";
        }

        $parts = explode('/', $lastQuotation->quotation_number);
        $nextSequence = str_pad((string) (((int) end($parts)) + 1), 3, '0', STR_PAD_LEFT);

        return "{$prefix}/{$dateCode}/{$nextSequence}";
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
