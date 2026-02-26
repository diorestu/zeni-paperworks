<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Setting;
use App\Models\Tax;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class QuotationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

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
            'quotation' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'quotation_number' => [
                'required',
                'string',
                Rule::unique('quotations', 'quotation_number')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'quotation_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:quotation_date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'selected_tax_ids' => 'nullable|array',
            'selected_tax_ids.*' => [
                'integer',
                Rule::exists('taxes', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
        ]);

        $quotation = DB::transaction(function () use ($validated) {
            $totals = $this->buildQuotationTotals($validated['items'], $validated['selected_tax_ids'] ?? []);

            $quotation = Quotation::create([
                'client_id' => $validated['client_id'],
                'quotation_number' => $validated['quotation_number'],
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
                    'product_id' => !empty($item['product_id']) ? $item['product_id'] : null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }
            return $quotation;
        });

        $this->notificationService->notifyUser($request->user(), [
            'type' => 'quotation.created',
            'title' => 'Quotation created',
            'message' => "Quotation {$quotation->quotation_number} has been created.",
            'href' => route('quotations.show', $quotation),
            'icon' => 'si:assignment-line',
        ]);

        return redirect()->route('quotations.index')->with('status', 'Quotation created successfully');
    }

    public function edit(Quotation $quotation): Response
    {
        return Inertia::render('Quotations/Create', [
            'clients' => Client::all(),
            'products' => Product::all(),
            'taxes' => Tax::where('is_active', true)->get(),
            'nextQuotationNumber' => $this->generateQuotationNumber(),
            'quotation' => $quotation->load(['items.product']),
        ]);
    }

    public function update(Request $request, Quotation $quotation): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'quotation_number' => [
                'required',
                'string',
                Rule::unique('quotations', 'quotation_number')
                    ->ignore($quotation->id)
                    ->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'quotation_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:quotation_date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'selected_tax_ids' => 'nullable|array',
            'selected_tax_ids.*' => [
                'integer',
                Rule::exists('taxes', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
        ]);

        DB::transaction(function () use ($validated, $quotation) {
            $totals = $this->buildQuotationTotals($validated['items'], $validated['selected_tax_ids'] ?? []);

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
                    'product_id' => !empty($item['product_id']) ? $item['product_id'] : null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }
        });

        $this->notificationService->notifyUser($request->user(), [
            'type' => 'quotation.updated',
            'title' => 'Quotation updated',
            'message' => "Quotation {$quotation->quotation_number} has been updated.",
            'href' => route('quotations.show', $quotation),
            'icon' => 'si:edit-line',
        ]);

        return redirect()->route('quotations.show', $validated['quotation_number'])->with('status', 'Quotation updated successfully');
    }

    public function show(Quotation $quotation): Response
    {
        return Inertia::render('Quotations/Show', [
            'quotation' => $quotation->load(['client', 'items.product', 'invoice']),
            'companyLogoUrl' => $this->resolveCompanyLogoUrl(),
            'companyProfile' => $this->resolveCompanyProfile(),
        ]);
    }

    public function convertToInvoice(Request $request, Quotation $quotation): RedirectResponse
    {
        if ($quotation->invoice_id) {
            return redirect()->back()->with('error', 'This quotation has already been converted to an invoice.');
        }

        DB::transaction(function () use ($quotation) {
            // Create invoice from quotation
            $invoice = Invoice::create([
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

        $quotation->refresh();
        $this->notificationService->notifyUser($request->user(), [
            'type' => 'quotation.converted_to_invoice',
            'title' => 'Converted to invoice',
            'message' => "Quotation {$quotation->quotation_number} has been converted to an invoice.",
            'href' => route('quotations.show', $quotation),
            'icon' => 'si:file-transfer-line',
        ]);

        return redirect()->route('quotations.index')->with('status', 'Quotation successfully converted to invoice');
    }

    public function downloadPdf(Request $request, Quotation $quotation)
    {
        abort_unless($quotation->company_id === $request->user()->company_id, 403);
        $variant = (string) $request->query('variant', 'classic');
        if (! in_array($variant, ['classic', 'modern', 'minimal'], true)) {
            $variant = 'classic';
        }

        $pdf = Pdf::loadView('quotations.pdf', [
            'quotation' => $quotation->load(['client', 'items.product']),
            'logoUrl' => $this->resolveCompanyLogoUrl(),
            'company' => $this->resolveCompanyProfile(),
            'variant' => $variant,
            'isFreePlan' => ($request->user()->plan_name ?? 'Free') === 'Free',
        ])->setPaper('a4');

        $filename = 'quotation-'.Str::slug($quotation->quotation_number).'.pdf';

        return $pdf->download($filename);
    }

    private function buildQuotationTotals(array $items, array $selectedTaxIds): array
    {
        $subtotal = collect($items)->sum(fn ($item) => $item['quantity'] * $item['unit_price']);
        $taxes = Tax::query()
            ->whereIn('id', $selectedTaxIds)
            ->get(['id', 'name', 'type', 'rate']);

        $taxTotal = 0;
        $appliedTaxes = $taxes->map(function ($tax) use (&$taxTotal, $subtotal) {
            $amount = round(($subtotal * (float) $tax->rate) / 100, 2);
            $signedAmount = $tax->type === 'add' ? $amount : -1 * $amount;
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

    private function resolveCompanyLogoUrl(): ?string
    {
        $logoPath = Setting::where('key', 'company_logo')->value('value');

        if (empty($logoPath)) {
            return null;
        }

        return Storage::disk('public')->url($logoPath);
    }

    private function resolveCompanyProfile(): array
    {
        return [
            'name' => Setting::where('key', 'company_name')->value('value') ?? '',
            'address' => Setting::where('key', 'company_address')->value('value') ?? '',
            'phone' => Setting::where('key', 'company_phone')->value('value') ?? '',
            'email' => Setting::where('key', 'company_email')->value('value') ?? '',
            'website' => Setting::where('key', 'company_website')->value('value') ?? '',
            'tax_id' => Setting::where('key', 'company_tax_id')->value('value') ?? '',
        ];
    }
}
