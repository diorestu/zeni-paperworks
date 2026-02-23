<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Subscription reminder</title>
</head>
<body style="font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; color: #1f2937;">
    <h2 style="margin: 0 0 12px;">Subscription reminder</h2>
    <p>Hi {{ $user->name }},</p>
    <p>Your <strong>{{ $planName }}</strong> plan will expire in 14 days on <strong>{{ $renewalDate }}</strong>.</p>
    <p>Estimated renewal amount: <strong>Rp{{ number_format($amount, 0, ',', '.') }}</strong></p>
    <p>Please complete your renewal before the due date to avoid service interruption.</p>
    <p>Thanks,<br>Paperwork Team</p>
</body>
</html>
