<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\InvoiceSentMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Tax;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Setting;
use App\Services\NotificationService;
use App\Services\PackageCatalogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly PackageCatalogService $packageCatalogService
    )
    {
    }

    public function index(): Response
    {
        return Inertia::render('Invoices/Index', [
            'invoices' => Invoice::with('client')->latest()->get(),
        ]);
    }

    public function create(Request $request): Response
    {
        $sourceInvoice = null;

        if ($request->filled('source_invoice')) {
            $sourceInvoice = Invoice::query()
                ->with(['items', 'client'])
                ->where('company_id', $request->user()->company_id)
                ->where('invoice_number', (string) $request->input('source_invoice'))
                ->first();
        }

        return Inertia::render('Invoices/Create', [
            'clients' => Client::latest()->get(),
            'products' => Product::all(),
            'bankAccounts' => auth()->user()->bankAccounts()->latest()->get(),
            'taxes' => Tax::where('is_active', true)->get(),
            'nextInvoiceNumber' => $this->generateInvoiceNumber(),
            'invoice' => null,
            'sourceInvoice' => $sourceInvoice ? [
                'id' => $sourceInvoice->id,
                'invoice_number' => $sourceInvoice->invoice_number,
                'client_id' => $sourceInvoice->client_id,
                'bank_account_id' => $sourceInvoice->bank_account_id,
                'notes' => $sourceInvoice->notes,
                'items' => $sourceInvoice->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                ]),
            ] : null,
        ]);
    }

    public function edit(Request $request, Invoice $invoice): Response
    {
        abort_unless($invoice->company_id === $request->user()->company_id, 403);

        return Inertia::render('Invoices/Create', [
            'clients' => Client::latest()->get(),
            'products' => Product::all(),
            'bankAccounts' => auth()->user()->bankAccounts()->latest()->get(),
            'taxes' => Tax::where('is_active', true)->get(),
            'nextInvoiceNumber' => $this->generateInvoiceNumber(),
            'invoice' => $invoice->load(['items.product']),
            'sourceInvoice' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;
        $activePlan = $this->resolveActivePlanName($user);
        $monthlyLimit = $this->planInvoiceLimit($activePlan);

        if ($monthlyLimit !== null) {
            $invoicesThisMonth = Invoice::query()
                ->where('company_id', $companyId)
                ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                ->count();

            if ($invoicesThisMonth >= $monthlyLimit) {
                throw ValidationException::withMessages([
                    'invoice_limit' => "Paket {$activePlan} hanya bisa membuat {$monthlyLimit} invoice per bulan. Upgrade paket untuk menambah limit.",
                ]);
            }
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'is_down_payment' => 'nullable|boolean',
            'parent_invoice_id' => [
                'nullable',
                Rule::exists('invoices', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'invoice_number' => [
                'required',
                'string',
                Rule::unique('invoices', 'invoice_number')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $invoice = DB::transaction(function () use ($validated) {
            $subtotal = collect($validated['items'])->sum(fn($item) => $item['quantity'] * $item['unit_price']);
            $tax_total = $subtotal * 0; // Default 0 for now
            $total = $subtotal + $tax_total;

            $invoice = Invoice::create([
                'client_id' => $validated['client_id'],
                'bank_account_id' => $validated['bank_account_id'] ?? null,
                'is_down_payment' => (bool) ($validated['is_down_payment'] ?? false),
                'parent_invoice_id' => $validated['parent_invoice_id'] ?? null,
                'invoice_number' => $validated['invoice_number'], // Frontend sends this, but we could enforce generation here if needed
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'subtotal' => $subtotal,
                'tax_total' => $tax_total,
                'total' => $total,
                'status' => 'draft',
            ]);

            foreach ($validated['items'] as $item) {
                $invoice->items()->create([
                    'product_id' => !empty($item['product_id']) ? $item['product_id'] : null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }
            return $invoice;
        });

        $this->notificationService->notifyUser($request->user(), [
            'type' => 'invoice.created',
            'title' => 'Invoice created',
            'message' => "Invoice {$invoice->invoice_number} has been created.",
            'href' => route('invoices.show', $invoice),
            'icon' => 'si:ballot-line',
        ]);

        return redirect()->route('invoices.index')->with('status', 'Invoice created successfully');
    }

    public function show(Invoice $invoice): Response
    {
        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice->load(['client', 'bankAccount', 'items.product', 'parentInvoice']),
            'companyLogoUrl' => $this->resolveCompanyLogoUrl(),
            'companyProfile' => $this->resolveCompanyProfile(),
        ]);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->company_id === $request->user()->company_id, 403);

        $companyId = $request->user()->company_id;
        $validated = $request->validate([
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'bank_account_id' => [
                'nullable',
                Rule::exists('bank_accounts', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'invoice_number' => [
                'required',
                'string',
                Rule::unique('invoices', 'invoice_number')
                    ->ignore($invoice->id)
                    ->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'nullable',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($invoice, $validated) {
            $subtotal = collect($validated['items'])->sum(fn($item) => $item['quantity'] * $item['unit_price']);
            $taxTotal = 0;
            $total = $subtotal + $taxTotal;

            $invoice->update([
                'client_id' => $validated['client_id'],
                'bank_account_id' => $validated['bank_account_id'] ?? null,
                'invoice_number' => $validated['invoice_number'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $total,
            ]);

            $invoice->items()->delete();
            foreach ($validated['items'] as $item) {
                $invoice->items()->create([
                    'product_id' => !empty($item['product_id']) ? $item['product_id'] : null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }
        });

        $this->notificationService->notifyUser($request->user(), [
            'type' => 'invoice.updated',
            'title' => 'Invoice updated',
            'message' => "Invoice {$invoice->invoice_number} has been updated.",
            'href' => route('invoices.show', $invoice),
            'icon' => 'si:edit-line',
        ]);

        return redirect()->route('invoices.show', $validated['invoice_number'])->with('status', 'Invoice updated successfully');
    }

    public function updateStatus(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->company_id === $request->user()->company_id, 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['draft', 'sent', 'paid', 'overdue', 'cancelled'])],
        ]);

        $invoice->update([
            'status' => $validated['status'],
        ]);

        $this->notificationService->notifyUser($request->user(), [
            'type' => 'invoice.status_updated',
            'title' => 'Invoice updated',
            'message' => "Invoice {$invoice->invoice_number} status changed to {$validated['status']}.",
            'href' => route('invoices.show', $invoice),
            'icon' => 'si:checklist-line',
        ]);

        return redirect()->back()->with('status', 'Invoice status updated');
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->company_id === $request->user()->company_id, 403);

        $invoiceNumber = $invoice->invoice_number;

        DB::transaction(function () use ($invoice) {
            $invoice->items()->delete();
            $invoice->delete();
        });

        $this->notificationService->notifyUser($request->user(), [
            'type' => 'invoice.deleted',
            'title' => 'Invoice deleted',
            'message' => "Invoice {$invoiceNumber} has been deleted.",
            'href' => route('invoices.index'),
            'icon' => 'si:bin-line',
        ]);

        return redirect()->route('invoices.index')->with('status', 'Invoice deleted successfully');
    }

    public function downloadPdf(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->company_id === $request->user()->company_id, 403);
        $variant = (string) $request->query('variant', 'classic');
        if (! in_array($variant, ['classic', 'modern', 'minimal'], true)) {
            $variant = 'classic';
        }

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice->load(['client', 'bankAccount', 'items.product']),
            'logoUrl' => $this->resolveCompanyLogoUrl(),
            'company' => $this->resolveCompanyProfile(),
            'variant' => $variant,
            'isFreePlan' => $this->resolveActivePlanName($request->user()) === 'Free',
        ])->setPaper('a4', 'portrait');

        $filename = 'invoice-'.Str::slug($invoice->invoice_number).'.pdf';

        return $pdf->download($filename);
    }

    public function downloadReceipt(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->company_id === $request->user()->company_id, 403);
        abort_unless($invoice->status === 'paid', 404, 'Receipt not available for unpaid invoice.');

        $pdf = Pdf::loadView('invoices.receipt', [
            'invoice' => $invoice->load(['client', 'items.product']),
            'company' => $this->resolveCompanyProfile(),
        ])->setPaper('a4', 'portrait');

        $filename = 'receipt-'.Str::slug($invoice->invoice_number).'.pdf';

        return $pdf->download($filename);
    }

    public function send(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->company_id === $request->user()->company_id, 403);

        $invoice->load(['client', 'items.product', 'bankAccount']);

        if (empty($invoice->client?->email)) {
            return redirect()->back()->with('error', 'Client email is missing. Please update client email first.');
        }

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'logoUrl' => $this->resolveCompanyLogoUrl(),
            'company' => $this->resolveCompanyProfile(),
            'variant' => 'classic',
            'isFreePlan' => $this->resolveActivePlanName($request->user()) === 'Free',
        ])->setPaper('a4', 'portrait');

        $pdfFilename = 'invoice-'.Str::slug($invoice->invoice_number).'.pdf';
        $pdfContent = $pdf->output();

        Mail::to($invoice->client->email)->send(new InvoiceSentMail(
            invoice: $invoice,
            companyProfile: $this->resolveCompanyProfile(),
            companyLogoUrl: $this->resolveCompanyLogoUrl(),
            pdfContent: $pdfContent,
            pdfFilename: $pdfFilename,
        ));

        if ($invoice->status === 'draft') {
            $invoice->update(['status' => 'sent']);
        }

        $this->notificationService->notifyUser($request->user(), [
            'type' => 'invoice.sent',
            'title' => 'Invoice sent',
            'message' => "Invoice {$invoice->invoice_number} was sent to {$invoice->client->email}.",
            'href' => route('invoices.show', $invoice),
            'icon' => 'si:mail-line',
        ]);

        return redirect()->back()->with('status', 'Invoice email sent successfully.');
    }

    private function resolveActivePlanName($user): string
    {
        $planName = $user->plan_name ?? 'Free';

        if ($planName !== 'Free' && $user->plan_renews_at && Carbon::parse($user->plan_renews_at)->isPast()) {
            return 'Free';
        }

        return $planName;
    }

    private function planInvoiceLimit(string $planName): ?int
    {
        return $this->packageCatalogService->invoiceLimit($planName);
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = Setting::where('key', 'invoice_prefix')->value('value') ?? 'INV';
        $dateCode = Carbon::now()->format('ymd');
        
        // Find the last invoice created today with this prefix
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
