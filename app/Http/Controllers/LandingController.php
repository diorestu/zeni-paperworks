<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Quotation;
use App\Services\PackageCatalogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function __construct(private readonly PackageCatalogService $packageCatalogService)
    {
    }

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

        $catalog = $this->packageCatalogService->all();
        $freeLimit = $catalog['Free']['invoice_limit'] ?? 10;
        $basicLimit = $catalog['Basic']['invoice_limit'] ?? 10;
        $proLimit = $catalog['Pro']['invoice_limit'] ?? 500;
        $enterpriseLimit = $catalog['Enterprise']['invoice_limit'] ?? null;

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
                ['title' => 'Invoice', 'description' => 'Buat, lacak, cetak, dan perbarui status invoice dalam satu alur.'],
                ['title' => 'Penawaran', 'description' => 'Buat penawaran dan konversi ke invoice dalam satu klik.'],
                ['title' => 'Klien', 'description' => 'Data klien terpusat lengkap dengan informasi perusahaan dan perpajakan.'],
                ['title' => 'Produk', 'description' => 'Kelola katalog produk dan harga agar pembuatan dokumen lebih cepat.'],
                ['title' => 'Pajak', 'description' => 'Kelola preset pajak yang bisa dipakai ulang saat membuat invoice.'],
                ['title' => 'Pengaturan', 'description' => 'Atur prefix invoice/penawaran serta profil bisnis.'],
                ['title' => 'Rekening Bank', 'description' => 'Simpan rekening penerimaan dan lampirkan pada invoice.'],
                ['title' => 'Tagihan', 'description' => 'Pantau paket berlangganan dan riwayat pembayaran dalam satu halaman.'],
            ],
            'plans' => [
                [
                    'name' => 'Free',
                    'price' => 'Rp0',
                    'period' => 'selamanya',
                    'highlights' => ["{$freeLimit} Invoice / bulan", '10 Penawaran / bulan', '10 Klien'],
                ],
                [
                    'name' => 'Basic',
                    'price' => 'Rp'.number_format((int) ($catalog['Basic']['monthly_price'] ?? 49000), 0, ',', '.'),
                    'period' => '/ bulan',
                    'highlights' => ["{$basicLimit} Invoice / bulan", '10 Penawaran / bulan', '100 Klien'],
                ],
                [
                    'name' => 'Pro',
                    'price' => 'Rp'.number_format((int) ($catalog['Pro']['monthly_price'] ?? 139000), 0, ',', '.'),
                    'period' => '/ bulan',
                    'highlights' => ["{$proLimit} Invoice / bulan", 'Penawaran tanpa batas', '500 Klien'],
                ],
                [
                    'name' => 'Enterprise',
                    'price' => 'Rp'.number_format((int) ($catalog['Enterprise']['monthly_price'] ?? 199000), 0, ',', '.'),
                    'period' => '/ bulan',
                    'highlights' => [$enterpriseLimit === null ? 'Invoice tanpa batas' : "{$enterpriseLimit} Invoice / bulan", 'Penawaran tanpa batas', 'Klien tanpa batas'],
                ],
            ],
            'isAuthenticated' => $request->user() !== null,
        ]);
    }
}
