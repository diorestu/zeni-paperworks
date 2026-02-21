<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('app.name', 'Zeni') }}</title>
    <link rel="icon" type="image/png" href="/img/logo/favicon.png">
    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if (filled(config('services.midtrans.client_key')))
        <script
            src="{{ config('services.midtrans.snap_url') }}"
            data-client-key="{{ config('services.midtrans.client_key') }}"></script>
    @endif
    @inertiaHead
</head>

<body class="antialiased">
    @inertia
</body>

</html>
