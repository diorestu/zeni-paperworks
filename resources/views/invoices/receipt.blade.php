<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 26px;
        }

        html,
        body,
        * {
            font-family: system-ui, -apple-system, sans-serif !important;
            font-weight: 400 !important;
            font-style: normal !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            margin: 0;
            padding: 26px;
            color: #1d1f21;
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
            color: #1d1f21;
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
            color: #1d1f21;
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
            color: #1d1f21;
            border-bottom: 1px solid #334155;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }

        .label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #1d1f21;
            margin-bottom: 2px;
        }

        .value-strong {
            font-size: 12px;
            font-weight: 400;
            color: #1d1f21;
            margin-bottom: 4px;
        }

        .muted {
            color: #1d1f21;
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
            color: #1d1f21;
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
            color: #1d1f21;
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
            color: #1d1f21;
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
            color: #1d1f21;
            margin-bottom: 6px;
        }

        .footer {
            margin-top: 22px;
            text-align: left;
            color: #1d1f21;
            font-size: 11px;
        }

        .footer .brand {
            color: #1d1f21;
            font-weight: 400;
            margin-top: 4px;
        }
    </style>
</head>

<body>
    @php
        $companyName = !empty($company['name']) ? $company['name'] : config('app.name', 'Paperwork');
        $subtotal = (float) $invoice->subtotal;
        $tax = (float) $invoice->tax_total;
        $total = (float) $invoice->total;

        $logoSetting = \App\Models\Setting::where('key', 'company_logo')->value('value');
        $logoSystemPath = public_path('img/logo/logo_colorful.png');

        if (!empty($logoSetting) && \Storage::disk('public')->exists($logoSetting)) {
            $logoSystemPath = \Storage::disk('public')->path($logoSetting);
        }

        $logoSrc = '';
        if (file_exists($logoSystemPath)) {
            $type = pathinfo($logoSystemPath, PATHINFO_EXTENSION);
            $type = $type === 'svg' ? 'svg+xml' : $type;
            $data = file_get_contents($logoSystemPath);
            $logoSrc = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
    @endphp

    <div class="receipt">
        <table class="topbar">
            <tr>
                <td>
                    @if ($logoSrc)
                        <img src="{{ $logoSrc }}" alt="Logo" class="logo">
                    @else
                        <div style="font-size:24px; font-weight:bold; color: #1d1f21;">{{ $companyName }}</div>
                    @endif
                </td>
                <td>
                    <div class="invoice-title">Payment Receipt</div>
                    <table class="meta-table">
                        <tr>
                            <td class="meta-label">Client Name</td>
                            <td class="meta-value">{{ $invoice->client->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Date Issued</td>
                            <td class="meta-value">{{ optional($invoice->invoice_date)->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Payment Date</td>
                            <td class="meta-value">{{ optional($invoice->updated_at)->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Invoice No.</td>
                            <td class="meta-value">{{ $invoice->invoice_number }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="section-grid">
            <tr>
                <td>
                    <div class="section-title">Company Info</div>
                    <div class="value-strong">{{ strtoupper($companyName) }}</div>
                    <p class="muted">{!! nl2br(e($company['address'] ?? '')) !!}</p>
                    <p class="muted">T: {{ $company['phone'] ?? '-' }}</p>
                    <p class="muted">E: {{ $company['email'] ?? '-' }}</p>
                </td>
                <td>
                    <div class="section-title">Billed To</div>
                    <div class="value-strong">{{ $invoice->client->name }}</div>
                    <p class="muted">{{ $invoice->client->company }}</p>
                    <p class="muted">{!! nl2br(e($invoice->client->address ?? '-')) !!}</p>
                    <p class="muted">{{ $invoice->client->email }}</p>
                    <p class="muted">{{ $invoice->client->phone }}</p>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Description</th>
                    <th class="right">Qty</th>
                    <th class="right">Price</th>
                    <th class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $idx => $item)
                    <tr>
                        @php
                            $lines = explode("\n", $item->description);
                            $title = array_shift($lines);
                        @endphp
                        <td>{{ $title }}</td>
                        <td>
                            @if (count($lines) > 0)
                                {!! nl2br(e(implode("\n", $lines))) !!}
                            @endif
                        </td>
                        <td class="right">{{ $item->quantity }}</td>
                        <td class="right">{{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                        <td class="right">{{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary">
            <tr>
                <td class="key">Subtotal</td>
                <td class="val">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
            </tr>
            @if ($tax > 0)
                <tr>
                    <td class="key">Tax</td>
                    <td class="val">Rp {{ number_format($tax, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr class="total">
                <td class="key">Total</td>
                <td class="val">Rp {{ number_format($total, 0, ',', '.') }}</td>
            </tr>
        </table>

        <div class="notes-wrap">
            <div class="notes-title">Notes</div>
            <p class="muted">This receipt is automatically generated by the system and is valid as proof of payment.
            </p>
        </div>

        <div class="footer">
            <div>{{ now()->format('d M Y') }}</div>
            <div class="brand">{{ $companyName }}</div>
        </div>
    </div>
</body>

</html>
