<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment successful</title>
</head>
<body style="font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; color: #1f2937;">
    <h2 style="margin: 0 0 12px;">Payment successful</h2>
    <p>Hi {{ $user->name }},</p>
    <p>Your payment for the <strong>{{ $invoice->plan_name }}</strong> plan has been received successfully.</p>
    <p>
        Invoice: <strong>{{ $invoice->invoice_number }}</strong><br>
        Amount: <strong>Rp{{ number_format((float) $invoice->amount, 0, ',', '.') }}</strong><br>
        Active until: <strong>{{ optional($invoice->period_end)->format('d M Y') }}</strong>
    </p>
    <p>Thank you for your subscription.</p>
    <p>Regards,<br>Paperwork Team</p>
</body>
</html>
