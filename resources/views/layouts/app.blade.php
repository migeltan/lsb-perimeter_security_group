<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Visitor Access Control')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex flex-col">

    <header class="bg-slate-900 text-white border-b-4 border-amber-500">
        <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap justify-between items-center gap-4">
            <div>
                <div class="text-xs uppercase tracking-wider text-amber-400 font-semibold">House of Representatives</div>
                <h1 class="text-lg font-bold">Visitor Access Control</h1>
            </div>
        </div>
        <div class="bg-slate-950/80 px-4">
            <div class="max-w-7xl mx-auto flex gap-2 py-2">
                <a href="{{ route('scanner.index') }}" class="px-4 py-2 rounded-lg font-semibold text-sm {{ request()->routeIs('scanner.*') ? 'bg-amber-500 text-slate-950' : 'text-slate-300 hover:bg-slate-800' }}">
                    <i class="fa-solid fa-qrcode"></i> Scanner
                </a>
                <a href="{{ route('passes.index') }}" class="px-4 py-2 rounded-lg font-semibold text-sm {{ request()->routeIs('passes.*') ? 'bg-amber-500 text-slate-950' : 'text-slate-300 hover:bg-slate-800' }}">
                    <i class="fa-solid fa-id-card"></i> Passes
                </a>
                <a href="{{ route('logs.index') }}" class="px-4 py-2 rounded-lg font-semibold text-sm {{ request()->routeIs('logs.*') ? 'bg-amber-500 text-slate-950' : 'text-slate-300 hover:bg-slate-800' }}">
                    <i class="fa-solid fa-list-check"></i> Logs
                </a>
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-7xl w-full mx-auto p-4 md:p-6 space-y-6">
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-300 text-emerald-800 px-4 py-3 rounded-xl text-sm">
                {{ session('success') }}
            </div>
        @endif
        @yield('content')
    </main>

</body>
</html>