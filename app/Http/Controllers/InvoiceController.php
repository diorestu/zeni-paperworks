<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Tax;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Invoices/Index', [
            'invoices' => Invoice::with('client')->latest()->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Invoices/Create', [
            'clients' => Client::all(),
            'products' => Product::all(),
            'bankAccounts' => auth()->user()->bankAccounts()->latest()->get(),
            'taxes' => Tax::where('is_active', true)->get(),
            'nextInvoiceNumber' => $this->generateInvoiceNumber(),
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
            'invoice_number' => [
                'required',
                'string',
                Rule::unique('invoices', 'invoice_number')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            $subtotal = collect($validated['items'])->sum(fn($item) => $item['quantity'] * $item['unit_price']);
            $tax_total = $subtotal * 0; // Default 0 for now
            $total = $subtotal + $tax_total;

            $invoice = Invoice::create([
                'client_id' => $validated['client_id'],
                'bank_account_id' => $validated['bank_account_id'] ?? null,
                'invoice_number' => $validated['invoice_number'], // Frontend sends this, but we could enforce generation here if needed
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
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
        });

        return redirect()->route('invoices.index')->with('status', 'Invoice created successfully');
    }

    public function show(Invoice $invoice): Response
    {
        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice->load(['client', 'items.product']),
        ]);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['draft', 'sent', 'paid', 'overdue', 'cancelled'])],
        ]);

        $invoice->update([
            'status' => $validated['status'],
        ]);

        return redirect()->back()->with('status', 'Invoice status updated');
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
        $limits = config('plans.invoice_limits', []);

        if (! array_key_exists($planName, $limits)) {
            return $limits['Free'] ?? 10;
        }

        return $limits[$planName];
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
}
