<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
</head>
<body style="font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; color: #1f2937; line-height: 1.5;">
    @if(!empty($companyLogoUrl))
        <p style="margin: 0 0 14px;">
            <img src="{{ $companyLogoUrl }}" alt="Company Logo" style="max-height: 36px; width: auto;">
        </p>
    @endif

    <h2 style="margin: 0 0 12px;">Invoice {{ $invoice->invoice_number }}</h2>

    <p style="margin: 0 0 12px;">
        Hi {{ $invoice->client->name }},
    </p>

    <p style="margin: 0 0 12px;">
        Please find your invoice details below:
    </p>

    <table style="border-collapse: collapse; width: 100%; max-width: 560px; margin-bottom: 14px;">
        <tr>
            <td style="padding: 4px 0; color: #64748b;">Invoice Number</td>
            <td style="padding: 4px 0; text-align: right; font-weight: 600;">{{ $invoice->invoice_number }}</td>
        </tr>
        <tr>
            <td style="padding: 4px 0; color: #64748b;">Issued Date</td>
            <td style="padding: 4px 0; text-align: right;">{{ optional($invoice->invoice_date)->format('d M Y') }}</td>
        </tr>
        <tr>
            <td style="padding: 4px 0; color: #64748b;">Due Date</td>
            <td style="padding: 4px 0; text-align: right;">{{ optional($invoice->due_date)->format('d M Y') }}</td>
        </tr>
        <tr>
            <td style="padding: 4px 0; color: #64748b;">Grand Total</td>
            <td style="padding: 4px 0; text-align: right; font-weight: 700;">Rp{{ number_format((float) $invoice->total, 0, ',', '.') }}</td>
        </tr>
    </table>

    @if(!empty($invoice->bankAccount))
        <p style="margin: 0 0 6px; font-weight: 600;">Payment Information</p>
        <p style="margin: 0 0 12px; color: #334155;">
            {{ $invoice->bankAccount->bank_name }} · {{ $invoice->bankAccount->account_number }} a.n {{ $invoice->bankAccount->account_name }}
        </p>
    @endif

    @if(!empty($invoice->notes))
        <p style="margin: 0 0 6px; font-weight: 600;">Notes</p>
        <p style="margin: 0 0 12px; color: #334155;">{{ $invoice->notes }}</p>
    @endif

    <p style="margin: 12px 0 0;">
        Regards,<br>
        {{ $companyProfile['name'] ?? config('app.name', 'Paperwork') }}
    </p>
</body>
</html>
