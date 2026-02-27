<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    @php
        $variant = in_array($variant ?? 'classic', ['classic', 'modern', 'minimal'], true) ? $variant : 'classic';
        $theme = match ($variant) {
            'modern' => [
                'primary' => '#1d1f21',
                'accent' => '#1d1f21',
                'bg' => '#ffffff',
                'box_bg' => '#f8fafc',
                'box_border' => '#f1f5f9',
                'table_head' => '#0f172a',
                'table_text' => '#ffffff',
            ],
            'minimal' => [
                'primary' => '#1d1f21',
                'accent' => '#1d1f21',
                'bg' => '#ffffff',
                'box_bg' => '#ffffff',
                'box_border' => '#e2e8f0',
                'table_head' => '#f8fafc',
                'table_text' => '#1e293b',
            ],
            default => [
                'primary' => '#1d1f21',
                'accent' => '#1d1f21',
                'bg' => '#ffffff',
                'box_bg' => '#f1f5f9',
                'box_border' => '#e2e8f0',
                'table_head' => '#000000',
                'table_text' => '#ffffff',
            ],
        };
    @endphp
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        body,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        table,
        th,
        td,
        div,
        span,
        p,
        label,
        strong {
            font-family: 'Helvetica', 'Arial', sans-serif !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            margin: 0;
            padding: 0;
            font-size: 12px;
            color: #1d1f21;
            line-height: 1.5;
            background: #fff;
        }

        .container {
            padding: 12mm 12mm;
        }

        .row {
            width: 100%;
            display: block;
            clear: both;
        }

        .col-left {
            width: 50%;
            float: left;
        }

        .col-right {
            width: 45%;
            float: right;
        }

        .clear {
            clear: both;
            height: 1px;
        }

        .branding {
            margin-bottom: 20px;
        }

        .branding h1 {
            margin: 0;
            font-size: 32px;
            font-weight: bold;
            color: {{ $theme['primary'] }};
            text-transform: uppercase;
            letter-spacing: -1px;
        }

        .branding .company-logo {
            float: right;
            max-height: 45px;
            width: auto;
            max-width: 250px;
        }

        .company-name {
            font-size: 12px;
            font-weight: 700;
            color: #1d1f21;
            margin-top: 5px;
        }

        .info-box {
            background: {{ $theme['box_bg'] }};
            border: 1px solid {{ $theme['box_border'] }};
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .label-sm {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            color: {{ $theme['accent'] }};
            margin-bottom: 8px;
        }

        .client-name {
            font-size: 13px;
            font-weight: 700;
            color: #1d1f21;
            margin-bottom: 6px;
        }

        .client-info {
            font-size: 10px;
            color: #1d1f21;
            line-height: 1.6;
        }

        .meta-list {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-list td {
            padding: 2px 0;
            border: 0;
            font-size: 11px;
            vertical-align: middle;
        }

        .meta-label {
            color: #1d1f21;
            width: 120px;
        }

        .meta-value {
            text-align: right;
            font-weight: 700;
            color: #1d1f21;
        }

        .items-container {
            margin-top: 40px;
            margin-bottom: 30px;
        }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.items-table th {
            background: {{ $theme['table_head'] }};
            color: {{ $theme['table_text'] }};
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 12px 15px;
            text-align: left;
        }

        table.items-table td {
            padding: 15px;
            font-size: 11px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }

        .item-title {
            font-weight: 700;
            color: #1d1f21;
            font-size: 11px;
            margin-bottom: 3px;
        }

        .item-details {
            font-size: 11px;
            color: #1d1f21;
            line-height: 1.4;
        }

        .summary-container {
            width: 280px;
            float: right;
            margin-bottom: 20px;
        }

        table.summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.summary-table td {
            padding: 10px 0;
            font-size: 13px;
        }

        .sum-label {
            color: #1d1f21;
            text-align: left;
        }

        .sum-value {
            font-weight: 700;
            text-align: right;
            color: #1d1f21;
        }

        .sum-total td {
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }

        .sum-total .sum-label {
            font-size: 13px;
            font-weight: bold;
            color: #1d1f21;
        }

        .sum-total .sum-value {
            font-size: 16px;
            font-weight: bold;
            color: {{ $theme['primary'] }};
        }

        .notes {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
            font-size: 13px;
            color: #1d1f21;
            font-style: italic;
            line-height: 1.6;
            clear: both;
        }

        .watermark {
            position: fixed;
            top: 45%;
            left: 50%;
            width: 400px;
            margin-left: -200px;
            opacity: 0.03;
            transform: rotate(-30deg);
            z-index: -1;
        }
    </style>
</head>

<body>
    @if ($isFreePlan ?? false)
        <div class="watermark">
            @php
                $watermarkPath = public_path('img/logo/logo_colorful.png');
                $watermarkSrc = '';
                if (file_exists($watermarkPath)) {
                    $wType = pathinfo($watermarkPath, PATHINFO_EXTENSION);
                    $wType = $wType === 'svg' ? 'svg+xml' : $wType;
                    $wData = file_get_contents($watermarkPath);
                    $watermarkSrc = 'data:image/' . $wType . ';base64,' . base64_encode($wData);
                }
            @endphp
            <img src="{{ $watermarkSrc }}" style="width: 100%;">
        </div>
    @endif

    <div class="container">
        <div class="branding">
            <div class="row">
                <div class="col-left">
                    <h1>Quotation</h1>
                    @if ($variant !== 'modern')
                        <div class="company-name">{{ $company['name'] ?: 'Company Name' }}</div>
                    @endif
                </div>
                <div class="col-right">
                    @php
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
                    <img src="{{ $logoSrc }}" class="company-logo">
                </div>
            </div>
            <div class="clear"></div>
        </div>

        <div class="info-box">
            <div class="row">
                <div class="col-left" style="padding-right: 20px;">
                    <div class="label-sm">Quotation For</div>
                    <div class="client-name">{{ $quotation->client->name }}</div>
                    <div class="client-info">
                        @if (!empty($quotation->client->company) && strcasecmp(trim((string) $quotation->client->company), trim((string) $quotation->client->name)) !== 0)
                            <strong style="color: #1d1f21;">{{ $quotation->client->company }}</strong><br>
                        @endif
                        {!! nl2br(e($quotation->client->address ?? '-')) !!}
                        @if (!empty($quotation->client->phone))
                            <br>T: {{ $quotation->client->phone }}
                        @endif
                        @if (!empty($quotation->client->email))
                            <br>E: {{ $quotation->client->email }}
                        @endif
                    </div>
                </div>
                <div class="col-right">
                    <table class="meta-list">
                        <tr>
                            <td class="meta-label">Quotation No.</td>
                            <td class="meta-value">{{ $quotation->quotation_number }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Date Issued</td>
                            <td class="meta-value">{{ optional($quotation->quotation_date)->format('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Valid Until</td>
                            <td class="meta-value">{{ optional($quotation->valid_until)->format('d F Y') }}</td>
                        </tr>
                    </table>
                    @if ($variant === 'modern')
                        <div
                            style="margin-top: 15px; border-top: 1px solid {{ $theme['box_border'] }}; padding-top: 12px;">
                            <div class="label-sm">From</div>
                            <div class="client-info" style="color: #1d1f21; font-weight: bold;">
                                {{ $company['name'] ?: 'Company Name' }}<br>
                                <span style="font-weight: normal; color: #1d1f21;">{!! nl2br(e($company['address'] ?? '-')) !!}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="clear"></div>
        </div>

        <div class="items-container">
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">No</th>
                        <th>Description</th>
                        <th style="width: 100px; text-align: right;">Price</th>
                        <th style="width: 50px; text-align: center;">Qty</th>
                        <th style="width: 120px; text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quotation->items as $idx => $item)
                        <tr>
                            <td style="text-align: center; color: #1d1f21;">{{ $idx + 1 }}</td>
                            <td>
                                @php
                                    $lines = explode("\n", $item->description);
                                    $title = array_shift($lines);
                                @endphp
                                <div class="item-title">{{ $title }}</div>
                                @if (count($lines) > 0)
                                    <div class="item-details">{!! nl2br(e(implode("\n", $lines))) !!}</div>
                                @endif
                                @if (!empty($item->product?->description))
                                    <div class="item-details" style="font-style: italic; margin-top: 2px;">
                                        {{ $item->product->description }}</div>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                Rp{{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                            <td style="text-align: center;">{{ $item->quantity }}</td>
                            <td style="text-align: right; font-weight: 700; color: #1d1f21;">
                                Rp{{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="row">
            <div class="summary-container">
                <table class="summary-table">
                    <tr>
                        <td class="sum-label">Subtotal</td>
                        <td class="sum-value">Rp{{ number_format((float) $quotation->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if ($quotation->tax_total != 0)
                        <tr>
                            <td class="sum-label">Tax Total</td>
                            <td class="sum-value">Rp{{ number_format((float) $quotation->tax_total, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                    <tr class="sum-total">
                        <td class="sum-label">Total Amount</td>
                        <td class="sum-value">Rp{{ number_format((float) $quotation->total, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
            <div class="clear"></div>
        </div>

        @if (!empty($quotation->notes))
            <div class="notes">
                <div class="label-sm" style="margin-bottom: 5px;">Terms & Conditions</div>
                {!! nl2br(e($quotation->notes)) !!}
            </div>
        @endif
    </div>
</body>

</html>
