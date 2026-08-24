<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Visitor Access Control')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    {{-- Theme stylesheet — see notes.md for where to place this file --}}
    <link rel="stylesheet" href="{{ asset('css/theme-govt.css') }}">
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex flex-col">

    <header class="navbar-govt">
        <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap justify-between items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="brand-logo">
                    <img src="{{ asset('images/lsb-icon.png') }}" alt="House of Representatives — Internal Security Group">
                </div>
                <div>
                    <div class="brand-eyebrow">House of Representatives &middot; Internal Security Group</div>
                    <h1 class="brand-title">Visitor Access Control</h1>
                </div>
            </div>
        </div>
        <div class="navbar-nav-govt px-4">
            <div class="max-w-7xl mx-auto flex gap-2 py-2">
                <a href="{{ route('scanner.index') }}" class="nav-pill {{ request()->routeIs('scanner.*') ? 'is-active' : '' }}">
                    <i class="fa-solid fa-qrcode"></i> Scanner
                </a>
                <a href="{{ route('passes.index') }}" class="nav-pill {{ request()->routeIs('passes.*') ? 'is-active' : '' }}">
                    <i class="fa-solid fa-id-card"></i> Passes
                </a>
                <a href="{{ route('logs.index') }}" class="nav-pill {{ request()->routeIs('logs.*') ? 'is-active' : '' }}">
                    <i class="fa-solid fa-list-check"></i> Logs
                </a>
            </div>
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
