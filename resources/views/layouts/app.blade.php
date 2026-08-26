<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Visitor Access Control')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/theme-govt.css') }}">
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex flex-col">

    <header class="navbar-govt">
        <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap justify-between items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="brand-logo">
                    <img src="{{ asset('images/lsb-icon.png') }}" alt="House of Representatives - Perimeter Security Group">
                </div>
                <span class="brand-title">House of Representatives &dash; Perimeter Security Group</span>
            </div>
            <nav class="flex gap-5">
                <a href="{{ route('scanner.index') }}" class="nav-link-govt {{ request()->routeIs('scanner.*') ? 'is-active' : '' }}">Scanner</a>
                <a href="{{ route('passes.index') }}" class="nav-link-govt {{ request()->routeIs('passes.*') ? 'is-active' : '' }}">Passes</a>
                <a href="{{ route('logs.index') }}" class="nav-link-govt {{ request()->routeIs('logs.*') ? 'is-active' : '' }}">Logs</a>
            </nav>
        </div>
    </header>

    <main class="flex-grow max-w-7xl w-full mx-auto p-4 md:p-6 space-y-6">
        @if (session('success'))
            <div class="alert-govt-success px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @yield('content')
    </main>

</body>
</html>
