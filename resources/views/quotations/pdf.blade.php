<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <style>
        @page { size: A4; margin: 0; }
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 0; color: #0f172a; }
        .page { width: 210mm; min-height: 297mm; padding: 18mm 16mm; box-sizing: border-box; }
        .row { display: table; width: 100%; }
        .col { display: table-cell; vertical-align: top; }
        .right { text-align: right; }
        .muted { color: #64748b; font-size: 11px; }
        h1 { margin: 0 0 8px; font-size: 28px; letter-spacing: .3px; }
        h2 { margin: 0 0 6px; font-size: 16px; }
        .box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-top: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th { background: #0f172a; color: #fff; font-size: 11px; text-transform: uppercase; letter-spacing: .6px; padding: 10px; text-align: left; }
        td { border-bottom: 1px solid #e2e8f0; padding: 10px; font-size: 12px; }
        .num { text-align: right; }
        .summary { width: 45%; margin-left: auto; margin-top: 14px; }
        .summary .rowline { display: table; width: 100%; padding: 6px 0; }
        .summary .rowline span { display: table-cell; }
        .summary .rowline span:last-child { text-align: right; }
        .grand { border-top: 1px solid #cbd5e1; margin-top: 4px; padding-top: 8px; font-weight: 700; }
    </style>
</head>
<body>
<div class="page">
    <div class="row">
        <div class="col" style="width:70%;">
            <h1>QUOTATION</h1>
            <h2>{{ $company['name'] ?: 'Company Name' }}</h2>
            <div class="muted">{!! nl2br(e($company['address'] ?? '-')) !!}</div>
            @if(!empty($company['tax_id']))<div class="muted">Tax ID: {{ $company['tax_id'] }}</div>@endif
        </div>
        <div class="col right" style="width:30%;">
            @if(!empty($logoUrl))
                <img src="{{ $logoUrl }}" alt="Logo" style="max-height:64px; width:auto;">
            @endif
        </div>
    </div>

    <div class="box">
        <div class="row">
            <div class="col" style="width:55%;">
                <div class="muted" style="font-weight:600; text-transform:uppercase;">Quotation For</div>
                <div style="font-weight:700; margin-top:4px;">{{ $quotation->client->name }}</div>
                @if(!empty($quotation->client->company))<div class="muted">{{ $quotation->client->company }}</div>@endif
                @if(!empty($quotation->client->address))<div class="muted">{{ $quotation->client->address }}</div>@endif
            </div>
            <div class="col" style="width:45%;">
                <div class="row"><div class="col muted">Quotation Number</div><div class="col right">{{ $quotation->quotation_number }}</div></div>
                <div class="row"><div class="col muted">Issued</div><div class="col right">{{ optional($quotation->quotation_date)->format('d M Y') }}</div></div>
                <div class="row"><div class="col muted">Valid Until</div><div class="col right">{{ optional($quotation->valid_until)->format('d M Y') }}</div></div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:8%;">No</th>
                <th style="width:46%;">Item</th>
                <th class="num" style="width:16%;">Price</th>
                <th class="num" style="width:12%;">Qty</th>
                <th class="num" style="width:18%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="num">Rp{{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">Rp{{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <div class="rowline"><span class="muted">Subtotal</span><span>Rp{{ number_format((float) $quotation->subtotal, 0, ',', '.') }}</span></div>
        <div class="rowline"><span class="muted">Tax</span><span>Rp{{ number_format((float) $quotation->tax_total, 0, ',', '.') }}</span></div>
        <div class="rowline grand"><span>Grand Total</span><span>Rp{{ number_format((float) $quotation->total, 0, ',', '.') }}</span></div>
    </div>
</div>
</body>
</html>
