<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Setting;
use App\Models\Tax;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QuotationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Quotations/Index', [
            'quotations' => Quotation::with('client')->latest()->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Quotations/Create', [
            'clients' => Client::all(),
            'products' => Product::all(),
            'taxes' => Tax::where('is_active', true)->get(),
            'nextQuotationNumber' => $this->generateQuotationNumber(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'quotation_number' => 'required|string|unique:quotations,quotation_number',
            'quotation_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:quotation_date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            $subtotal = collect($validated['items'])->sum(fn($item) => $item['quantity'] * $item['unit_price']);
            $tax_total = $subtotal * 0;
            $total = $subtotal + $tax_total;

            $quotation = Quotation::create([
                'client_id' => $validated['client_id'],
                'quotation_number' => $validated['quotation_number'],
                'quotation_date' => $validated['quotation_date'],
                'valid_until' => $validated['valid_until'],
                'notes' => $validated['notes'] ?? null,
                'subtotal' => $subtotal,
                'tax_total' => $tax_total,
                'total' => $total,
                'status' => 'draft',
            ]);

            foreach ($validated['items'] as $item) {
                $quotation->items()->create([
                    'product_id' => !empty($item['product_id']) ? $item['product_id'] : null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }
        });

        return redirect()->route('quotations.index')->with('status', 'Quotation created successfully');
    }

    public function show(Quotation $quotation): Response
    {
        return Inertia::render('Quotations/Show', [
            'quotation' => $quotation->load(['client', 'items.product', 'invoice']),
        ]);
    }

    public function convertToInvoice(Quotation $quotation): RedirectResponse
    {
        if ($quotation->invoice_id) {
            return redirect()->back()->with('error', 'This quotation has already been converted to an invoice.');
        }

        DB::transaction(function () use ($quotation) {
            // Create invoice from quotation
            $invoice = Invoice::create([
                'client_id' => $quotation->client_id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_date' => now(),
                'due_date' => now()->addDays(7),
                'subtotal' => $quotation->subtotal,
                'tax_total' => $quotation->tax_total,
                'total' => $quotation->total,
                'status' => 'draft',
                'notes' => $quotation->notes,
            ]);

            // Copy items from quotation to invoice
            foreach ($quotation->items as $item) {
                $invoice->items()->create([
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ]);
            }

            // Update quotation status and link to invoice
            $quotation->update([
                'status' => 'accepted',
                'invoice_id' => $invoice->id,
            ]);
        });

        return redirect()->route('quotations.index')->with('status', 'Quotation successfully converted to invoice');
    }

    private function generateQuotationNumber(): string
    {
        $prefix = Setting::where('key', 'quotation_prefix')->value('value') ?? 'QUO';
        $dateCode = Carbon::now()->format('ymd');
        
        $lastQuotation = Quotation::where('quotation_number', 'like', "{$prefix}/{$dateCode}/%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastQuotation) {
            $parts = explode('/', $lastQuotation->quotation_number);
            $lastSequence = (int) end($parts);
            $nextSequence = str_pad($lastSequence + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nextSequence = '001';
        }

        return "{$prefix}/{$dateCode}/{$nextSequence}";
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = Setting::where('key', 'invoice_prefix')->value('value') ?? 'INV';
        $dateCode = Carbon::now()->format('ymd');
        
        $lastInvoice = Invoice::where('invoice_number', 'like', "{$prefix}/{$dateCode}/%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInvoice) {
            $parts = explode('/', $lastInvoice->invoice_number);
            $lastSequence = (int) end($parts);
            $nextSequence = str_pad($lastSequence + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nextSequence = '001';
        }

        return "{$prefix}/{$dateCode}/{$nextSequence}";
    }
}
