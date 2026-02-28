<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('app.name', 'Zeni') }}</title>
    <link rel="icon" type="image/png" href="/img/logo/sq_white_rounded.png">
    @php($authUser = auth()->user())
    @if (!$authUser)
        @routes(['shared', 'guest'])
    @elseif ($authUser->isSuperAdmin())
        @routes(['shared', 'auth-common', 'super-admin'])
    @elseif ($authUser->isAdmin())
        @routes(['shared', 'auth-common', 'auth-documents', 'auth-admin'])
    @else
        @routes(['shared', 'auth-common', 'auth-documents', 'auth-user'])
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>

<body class="antialiased">
    @inertia
</body>

</html>
