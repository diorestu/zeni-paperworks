<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $status ?? 500 }} {{ $title ?? 'Error' }} - Paperwork</title>
</head>
<body style="margin:0; background:#f8fafc; font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif; color:#0f172a;">
    <div style="min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px;">
        <div style="width:100%; max-width:560px; background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:32px; text-align:center; box-shadow:0 8px 30px rgba(15,23,42,0.06);">
            <p style="margin:0; font-size:11px; font-weight:600; letter-spacing:.12em; text-transform:uppercase; color:#94a3b8;">Paperwork</p>
            <p style="margin:18px 0 0; font-size:42px; font-weight:600; color:#0b2d6b;">{{ $status ?? 500 }}</p>
            <h1 style="margin:6px 0 0; font-size:28px; font-weight:600;">{{ $title ?? 'Error' }}</h1>
            <p style="margin:12px 0 0; font-size:14px; color:#475569;">{{ $message ?? 'An unexpected error occurred.' }}</p>

            <div style="margin-top:24px;">
                <a href="{{ route('landing') }}" style="display:inline-block; padding:10px 18px; border:1px solid #e2e8f0; border-radius:10px; text-decoration:none; font-size:12px; font-weight:600; color:#334155; margin-right:8px;">
                    Back to Home
                </a>
                <a href="javascript:history.back()" style="display:inline-block; padding:10px 18px; border-radius:10px; text-decoration:none; font-size:12px; font-weight:600; color:#fff; background:#0b2d6b;">
                    Go Back
                </a>
            </div>
        </div>
    </div>
</body>
</html>
