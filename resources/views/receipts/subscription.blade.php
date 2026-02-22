<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi Pembayaran {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 26px;
        }
        html, body, * {
            font-family: system-ui, -apple-system, sans-serif !important;
            font-weight: 400 !important;
            font-style: normal !important;
        }
        body {
            margin: 0;
            padding: 26px;
            color: #0f172a;
            background: #ffffff;
            font-size: 12px;
            line-height: 1.45;
        }
        .receipt {
            max-width: 760px;
            margin: 0 auto;
        }
        .topbar {
            width: 100%;
            margin-bottom: 22px;
            border-collapse: collapse;
        }
        .topbar td {
            vertical-align: top;
        }
        .logo {
            width: 110px;
            height: auto;
        }
        .invoice-title {
            text-align: right;
            font-size: 28px;
            font-weight: 400;
            color: #0b2d6b;
            margin-bottom: 8px;
        }
        .meta-table {
            margin-left: auto;
            border-collapse: collapse;
            width: 320px;
        }
        .meta-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .meta-label {
            width: 120px;
            color: #64748b;
            text-align: left;
        }
        .meta-value {
            text-align: right;
            font-family: system-ui, -apple-system, sans-serif !important;
            font-weight: 400;
        }
        .section-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .section-grid td {
            width: 50%;
            vertical-align: top;
            padding-right: 16px;
        }
        .section-grid td:last-child {
            padding-right: 0;
            padding-left: 16px;
        }
        .section-title {
            font-size: 12px;
            font-weight: 400;
            color: #334155;
            border-bottom: 1px solid #334155;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 2px;
        }
        .value-strong {
            font-size: 12px;
            font-weight: 400;
            color: #0b2d6b;
            margin-bottom: 4px;
        }
        .muted {
            color: #475569;
            margin: 0;
        }
        .items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .items thead th {
            background: #243b53;
            color: #ffffff;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 8px 7px;
            text-align: left;
            font-weight: 400;
        }
        .items thead th.right,
        .items tbody td.right {
            text-align: right;
        }
        .items tbody td {
            border-bottom: 1px solid #e2e8f0;
            padding: 8px 7px;
            color: #1e293b;
            vertical-align: top;
        }
        .summary {
            width: 280px;
            margin-left: auto;
            border-collapse: collapse;
        }
        .summary td {
            padding: 3px 0;
        }
        .summary .key {
            color: #475569;
            text-align: left;
        }
        .summary .val {
            text-align: right;
            font-weight: 400;
        }
        .summary .total td {
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            font-weight: 700 !important;
            color: #0b2d6b;
        }
        .notes-wrap {
            margin-top: 18px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
        .notes-title {
            font-size: 11px;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #334155;
            margin-bottom: 6px;
        }
        .footer {
            margin-top: 22px;
            text-align: left;
            color: #64748b;
            font-size: 11px;
        }
        .footer .brand {
            color: #0b2d6b;
            font-weight: 400;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    @php
        $companyName = config('app.name', 'Paperwork');
        $subtotal = (float) $invoice->amount;
        $tax = 0;
        $discount = 0;
        $total = $subtotal - $discount + $tax;
    @endphp

    <div class="receipt">
        <table class="topbar">
            <tr>
                <td>
                    <img src="{{ public_path('img/logo/logo.png') }}" alt="Logo" class="logo">
                </td>
                <td>
                    <div class="invoice-title">Payment Receipt</div>
                    <table class="meta-table">
                        <tr>
                            <td class="meta-label">Referensi</td>
                            <td class="meta-value">{{ $invoice->external_order_id ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Tanggal</td>
                            <td class="meta-value">{{ optional($invoice->invoice_date)->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Jatuh Tempo</td>
                            <td class="meta-value">{{ optional($invoice->due_date)->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">No. Invoice</td>
                            <td class="meta-value">{{ $invoice->invoice_number }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="section-grid">
            <tr>
                <td>
                    <div class="section-title">Info Perusahaan</div>
                    <div class="value-strong">{{ strtoupper($companyName) }}</div>
                    <p class="muted">Dokumen pembayaran berlangganan.</p>
                    <p class="muted">Metode: {{ strtoupper($invoice->payment_method ?? 'midtrans') }}</p>
                    <p class="muted">Tanggal bayar: {{ optional($invoice->paid_at)->format('d M Y H:i') ?? '-' }}</p>
                </td>
                <td>
                    <div class="section-title">Tagihan Untuk</div>
                    <div class="value-strong">{{ $invoice->user->name }}</div>
                    <p class="muted">{{ $invoice->user->email }}</p>
                    <p class="muted">Paket: {{ $invoice->plan_name }}</p>
                    <p class="muted">Periode: {{ optional($invoice->period_start)->format('d M Y') }} - {{ optional($invoice->period_end)->format('d M Y') }}</p>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Deskripsi</th>
                    <th class="right">Qty</th>
                    <th class="right">Harga</th>
                    <th class="right">Diskon</th>
                    <th class="right">Pajak</th>
                    <th class="right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Langganan {{ $invoice->plan_name }}</td>
                    <td>Pembayaran paket {{ $invoice->plan_name }} untuk periode {{ optional($invoice->period_start)->format('d M Y') }} - {{ optional($invoice->period_end)->format('d M Y') }}</td>
                    <td class="right">1</td>
                    <td class="right">{{ number_format($subtotal, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($discount, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($tax, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($total, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <table class="summary">
            <tr>
                <td class="key">Subtotal</td>
                <td class="val">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="key">Total Diskon</td>
                <td class="val">Rp {{ number_format($discount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="key">Pajak</td>
                <td class="val">Rp {{ number_format($tax, 0, ',', '.') }}</td>
            </tr>
            <tr class="total">
                <td class="key">Total</td>
                <td class="val">Rp {{ number_format($total, 0, ',', '.') }}</td>
            </tr>
        </table>

        <div class="notes-wrap">
            <div class="notes-title">Keterangan</div>
            <p class="muted">Kwitansi ini dibuat otomatis oleh sistem dan sah sebagai bukti pembayaran.</p>
        </div>

        <div class="footer">
            <div>{{ now()->format('d M Y') }}</div>
            <div class="brand">{{ $companyName }}</div>
        </div>
    </div>
</body>
</html>
