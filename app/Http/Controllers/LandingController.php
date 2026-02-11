<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function index(Request $request): Response
    {
        $invoiceStatus = Invoice::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $quotationStatus = Quotation::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('Landing', [
            'stats' => [
                'clients' => Client::query()->count(),
                'products' => Product::query()->count(),
                'invoices' => Invoice::query()->count(),
                'quotations' => Quotation::query()->count(),
                'invoice_paid' => (int) ($invoiceStatus['paid'] ?? 0),
                'quotation_accepted' => (int) ($quotationStatus['accepted'] ?? 0),
            ],
            'modules' => [
                ['title' => 'Invoices', 'description' => 'Create, track, print, and update invoice status in one flow.'],
                ['title' => 'Quotations', 'description' => 'Generate quotations and convert to invoices with one click.'],
                ['title' => 'Clients', 'description' => 'Centralized client records with company and tax-related info.'],
                ['title' => 'Products', 'description' => 'Maintain product catalog and pricing for fast document creation.'],
                ['title' => 'Taxes', 'description' => 'Manage reusable tax presets and apply them on invoice creation.'],
                ['title' => 'Settings', 'description' => 'Configure invoice/quotation prefixes and business profile details.'],
                ['title' => 'Bank Accounts', 'description' => 'Store receiving bank accounts and attach them to invoices.'],
                ['title' => 'Billing', 'description' => 'Track package plans and payment history from one billing page.'],
            ],
            'plans' => [
                [
                    'name' => 'Free',
                    'price' => 'Rp0',
                    'period' => 'forever',
                    'highlights' => ['10 Invoices / month', '10 Quotations / month', '10 Clients'],
                ],
                [
                    'name' => 'Basic',
                    'price' => 'Rp39.000',
                    'period' => '/ month',
                    'highlights' => ['100 Invoices / month', '10 Quotations / month', '100 Clients'],
                ],
                [
                    'name' => 'Pro',
                    'price' => 'Rp99.000',
                    'period' => '/ month',
                    'highlights' => ['500 Invoices / month', 'Unlimited Quotations', '500 Clients'],
                ],
                [
                    'name' => 'Enterprise',
                    'price' => 'Rp249.000',
                    'period' => '/ month',
                    'highlights' => ['Unlimited Invoices', 'Unlimited Quotations', 'Unlimited Clients'],
                ],
            ],
            'isAuthenticated' => $request->user() !== null,
        ]);
    }
}
