<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #0f172a;
            background: #ffffff;
            margin: 0;
            padding: 24px;
        }
        .container {
            max-width: 760px;
            margin: 0 auto;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        .header {
            padding: 20px 24px;
            background: #07304a;
            color: #ffffff;
        }
        .header h1 {
            margin: 0 0 6px;
            font-size: 24px;
        }
        .header p {
            margin: 0;
            opacity: 0.9;
        }
        .content {
            padding: 24px;
        }
        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }
        .detail-table tr {
            border-bottom: 1px dashed #e2e8f0;
        }
        .detail-table tr:last-child {
            border-bottom: none;
        }
        .detail-table td {
            padding: 10px 0;
            vertical-align: top;
        }
        .label-cell {
            color: #64748b;
            width: 42%;
        }
        .value-cell {
            font-weight: 600;
            text-align: right;
        }
        .total {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 2px solid #e2e8f0;
            font-size: 20px;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
        }
        .footer {
            padding: 18px 24px;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Payment Receipt</h1>
            <p>Subscription payment confirmation</p>
        </div>
        <div class="content">
            <table class="detail-table">
                <tr>
                    <td class="label-cell">Receipt Number</td>
                    <td class="value-cell">{{ $invoice->invoice_number }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Customer</td>
                    <td class="value-cell">{{ $invoice->user->name }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Email</td>
                    <td class="value-cell">{{ $invoice->user->email }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Plan</td>
                    <td class="value-cell">{{ $invoice->plan_name }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Billing Period</td>
                    <td class="value-cell">{{ optional($invoice->period_start)->format('d M Y') }} - {{ optional($invoice->period_end)->format('d M Y') }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Paid At</td>
                    <td class="value-cell">{{ optional($invoice->paid_at)->format('d M Y H:i') ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Payment Method</td>
                    <td class="value-cell">{{ strtoupper($invoice->payment_method ?? 'midtrans') }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Order ID</td>
                    <td class="value-cell">{{ $invoice->external_order_id ?? '-' }}</td>
                </tr>
            </table>
            <div class="total">
                <span>Total Paid</span>
                <span>Rp{{ number_format((float) $invoice->amount, 0, ',', '.') }}</span>
            </div>
        </div>
        <div class="footer">
            Generated on {{ now()->format('d M Y H:i') }}. This receipt is generated automatically by the system.
        </div>
    </div>
</body>
</html>
