<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    @php
        $variant = in_array(($variant ?? 'classic'), ['classic', 'modern', 'minimal'], true) ? $variant : 'classic';
        $theme = match ($variant) {
            'modern' => [
                'top_border' => '8px solid #0f172a',
                'table_head_bg' => '#0f172a',
                'table_head_text' => '#ffffff',
                'table_head_border' => '0',
                'panel_bg' => '#f1f5f9',
                'panel_border' => '#dbe2ea',
                'title_color' => '#0f172a',
                'label_color' => '#475569',
                'text_color' => '#0f172a',
                'title_weight' => '700',
            ],
            'minimal' => [
                'top_border' => '1px solid #e2e8f0',
                'table_head_bg' => '#ffffff',
                'table_head_text' => '#0f172a',
                'table_head_border' => '1px solid #e2e8f0',
                'panel_bg' => '#ffffff',
                'panel_border' => '#e2e8f0',
                'title_color' => '#0f172a',
                'label_color' => '#64748b',
                'text_color' => '#0f172a',
                'title_weight' => '700',
            ],
            default => [
                'top_border' => '6px solid #000000',
                'table_head_bg' => '#000000',
                'table_head_text' => '#ffffff',
                'table_head_border' => '0',
                'panel_bg' => '#f8fafc',
                'panel_border' => '#e2e8f0',
                'title_color' => '#0f172a',
                'label_color' => '#64748b',
                'text_color' => '#0f172a',
                'title_weight' => '700',
            ],
        };
    @endphp
    <style>
        @page { size: A4 portrait; margin: 0; }
        body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; margin: 0; color: {{ $theme['text_color'] }}; }
        .page { width: 210mm; min-height: 297mm; padding: 18mm 16mm; box-sizing: border-box; border-top: {{ $theme['top_border'] }}; }
        .row { display: table; width: 100%; }
        .col { display: table-cell; vertical-align: top; }
        .right { text-align: right; }
        .muted { color: {{ $theme['label_color'] }}; font-size: 11px; }
        .label { color: {{ $theme['label_color'] }}; font-size: 10px; text-transform: uppercase; letter-spacing: .8px; }
        h1 { margin: 0 0 8px; font-size: 28px; letter-spacing: .3px; color: {{ $theme['title_color'] }}; font-weight: {{ $theme['title_weight'] }}; }
        h2 { margin: 0 0 6px; font-size: 16px; font-weight: 600; }
        .box { background: {{ $theme['panel_bg'] }}; border: 1px solid {{ $theme['panel_border'] }}; border-radius: 10px; padding: 12px; margin-top: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th { background: {{ $theme['table_head_bg'] }}; color: {{ $theme['table_head_text'] }}; border-bottom: {{ $theme['table_head_border'] }}; font-size: 11px; text-transform: uppercase; letter-spacing: .6px; padding: 10px; text-align: left; font-weight: 600; }
        td { border-bottom: 1px solid #e2e8f0; padding: 10px; font-size: 12px; }
        .num { text-align: right; }
        .summary { width: 45%; margin-left: auto; margin-top: 14px; }
        .summary .rowline { display: table; width: 100%; padding: 6px 0; }
        .summary .rowline span { display: table-cell; }
        .summary .rowline span:last-child { text-align: right; }
        .grand { border-top: 1px solid #cbd5e1; margin-top: 4px; padding-top: 8px; font-weight: 700; }
        .meta-table { width: 100%; border-collapse: collapse; margin-top: 2px; }
        .meta-table td { border: 0; padding: 3px 0; font-size: 12px; }
        .meta-table td:last-child { text-align: right; }
        .divider { margin-top: 14px; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
<div class="page">
    <div class="row">
        <div class="col" style="width:70%;">
            <h1>INVOICE</h1>
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
        @if($variant === 'modern')
            <div class="row">
                <div class="col" style="width:50%; padding-right:14px;">
                    <div class="label">From</div>
                    <div style="font-weight:600; margin-top:4px;">{{ $company['name'] ?: 'Company Name' }}</div>
                    <div class="muted" style="margin-top:4px;">{!! nl2br(e($company['address'] ?? '-')) !!}</div>
                    @if(!empty($company['phone']))<div class="muted">{{ $company['phone'] }}</div>@endif
                    @if(!empty($company['email']))<div class="muted">{{ $company['email'] }}</div>@endif
                </div>
                <div class="col" style="width:50%;">
                    <div class="label">Bill To</div>
                    <div style="font-weight:600; margin-top:4px;">{{ $invoice->client->name }}</div>
                    @if(!empty($invoice->client->company))<div class="muted">{{ $invoice->client->company }}</div>@endif
                    @if(!empty($invoice->client->address))<div class="muted">{{ $invoice->client->address }}</div>@endif
                    @if(!empty($invoice->client->phone))<div class="muted">{{ $invoice->client->phone }}</div>@endif
                    <table class="meta-table">
                        <tr><td class="label">Invoice Number</td><td>{{ $invoice->invoice_number }}</td></tr>
                        <tr><td class="label">Issued</td><td>{{ optional($invoice->invoice_date)->format('d M Y') }}</td></tr>
                        <tr><td class="label">Due Date</td><td>{{ optional($invoice->due_date)->format('d M Y') }}</td></tr>
                    </table>
                </div>
            </div>
        @else
            <div class="row">
                <div class="col" style="width:55%;">
                    <div class="label">Bill To</div>
                    <div style="font-weight:600; margin-top:4px;">{{ $invoice->client->name }}</div>
                    @if(!empty($invoice->client->company))<div class="muted">{{ $invoice->client->company }}</div>@endif
                    @if(!empty($invoice->client->address))<div class="muted">{{ $invoice->client->address }}</div>@endif
                    @if(!empty($invoice->client->phone))<div class="muted">{{ $invoice->client->phone }}</div>@endif
                </div>
                <div class="col" style="width:45%;">
                    <table class="meta-table">
                        <tr><td class="label">Invoice Number</td><td>{{ $invoice->invoice_number }}</td></tr>
                        <tr><td class="label">Issued</td><td>{{ optional($invoice->invoice_date)->format('d M Y') }}</td></tr>
                        <tr><td class="label">Due Date</td><td>{{ optional($invoice->due_date)->format('d M Y') }}</td></tr>
                    </table>
                </div>
            </div>
        @endif
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
            @foreach($invoice->items as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>
                        {{ $item->description }}
                        @if(!empty($item->product?->description))
                            <div class="muted" style="margin-top:4px;">{{ $item->product->description }}</div>
                        @endif
                    </td>
                    <td class="num">Rp{{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">Rp{{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <div class="rowline"><span class="muted">Subtotal</span><span>Rp{{ number_format((float) $invoice->subtotal, 0, ',', '.') }}</span></div>
        <div class="rowline grand"><span>Grand Total</span><span>Rp{{ number_format((float) $invoice->total, 0, ',', '.') }}</span></div>
    </div>

    @if($invoice->bankAccount)
        <div class="box" style="margin-top:18px;">
            <div class="label">Payment Information</div>
            <div class="muted" style="margin-top:6px; line-height:1.45;">
                Please transfer payment to {{ $invoice->bankAccount->bank_name }},
                account number {{ $invoice->bankAccount->account_number }}
                under name {{ $invoice->bankAccount->account_name }}.
            </div>
            @if(!empty($invoice->client?->phone))
                <div class="muted" style="margin-top:4px; line-height:1.45;">
                    After payment, please send confirmation to {{ $invoice->client->phone }}.
                </div>
            @endif
        </div>
    @endif

    @if(!empty($invoice->notes))
        <div class="divider"></div>
        <div style="margin-top:10px;">
            <div class="label">Terms &amp; Conditions</div>
            <div class="muted" style="margin-top:6px; line-height:1.45;">{{ $invoice->notes }}</div>
        </div>
    @endif
</div>
</body>
</html>
