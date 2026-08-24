@extends('layouts.app')
@section('title', 'Visitor Pass Management')

@section('content')
<div class="card-govt p-5 shadow-sm flex flex-wrap justify-between items-center gap-4">
    <div>
        <h2 class="text-lg font-bold text-slate-800">Visitor pass registry</h2>
        <p class="text-xs text-slate-500">5 passes per building — assign a visitor to issue and print.</p>
    </div>
    <button onclick="document.getElementById('registerModal').classList.remove('hidden')" class="btn-govt-gold px-4 py-2 rounded-xl text-xs">
        <i class="fa-solid fa-user-plus"></i> Register visitor &amp; issue pass
    </button>
</div>

<div class="flex overflow-x-auto gap-2 pb-2">
    <a href="{{ route('passes.index') }}" class="pill-filter {{ !request('building') ? 'is-active' : '' }}">All buildings</a>
    @foreach ($buildings as $b)
        <a href="{{ route('passes.index', ['building' => $b->code]) }}" class="pill-filter flex items-center gap-1.5 {{ request('building') === $b->code ? 'is-active' : '' }}">
            <span class="w-2.5 h-2.5 rounded-full" style="background:{{ $b->color_hex }}"></span> {{ $b->name }}
        </a>
    @endforeach
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    @foreach ($passes as $p)
        <div class="pass-card shadow-sm">
            <div class="p-4 pass-card-header flex justify-between items-start">
                <div>
                    <span class="pass-card-eyebrow">House of Reps</span>
                    <div class="pass-card-building">{{ $p->building->name }}</div>
                    <div class="text-xs font-semibold text-slate-500">Visitor pass</div>
                </div>
                <span class="badge-status {{ $p->status === 'active' ? 'badge-authorized' : 'badge-info' }}">
                    {{ ucfirst($p->status) }}
                </span>
            </div>
            <div class="p-4 space-y-2 text-xs">
                <div class="flex justify-between bg-slate-50 p-2 rounded-lg font-mono">
                    <span class="text-slate-400">Pass #:</span>
                    <span class="font-bold text-slate-800 text-sm">{{ $p->pass_number }}</span>
                </div>
                <div>
                    <span class="eyebrow-label" style="color: var(--ink-muted); font-size: 0.65rem;">Visitor</span>
                    <div class="font-bold text-slate-800 truncate">{{ $p->visitor_name ?: 'Unassigned' }}</div>
                </div>
            </div>
            <div class="px-4 py-2 pass-card-footer flex justify-between items-center" style="background:{{ $p->building->color_hex }}">
                <div class="text-xs font-bold">{{ $p->building->color_name }} badge</div>
                <a href="{{ route('passes.show', $p) }}" class="bg-white/20 hover:bg-white/30 text-white px-2.5 py-1 rounded text-xs font-bold">
                    <i class="fa-solid fa-qrcode"></i> View QR
                </a>
            </div>
        </div>
    @endforeach
</div>

<div id="registerModal" class="fixed inset-0 bg-slate-900/60 z-50 hidden flex items-center justify-center p-4">
    <div class="modal-govt-panel shadow-2xl max-w-md w-full p-6 space-y-4">
        <h3 class="font-bold text-slate-800">Register visitor &amp; issue pass</h3>
        <form method="POST" action="{{ route('passes.register') }}" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block mb-1">Visitor full name *</label>
                <input type="text" name="visitor_name" required class="w-full px-3 py-2">
            </div>
            <div>
                <label class="block mb-1">Government ID / reference *</label>
                <input type="text" name="id_ref" required class="w-full px-3 py-2">
            </div>
            <div>
                <label class="block mb-1">Purpose of visit *</label>
                <input type="text" name="purpose" required class="w-full px-3 py-2">
            </div>
            <div>
                <label class="block mb-1">Select pass *</label>
                <select name="pass_id" required class="w-full px-3 py-2 bg-slate-50 font-mono">
                    @foreach ($passes as $p)
                        <option value="{{ $p->id }}">[{{ $p->building->name }}] Pass {{ $p->pass_number }} - ({{ $p->visitor_name ? 'Assigned: '.$p->visitor_name : 'Available' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('registerModal').classList.add('hidden')" class="px-4 py-2 rounded-lg btn-govt-ghost">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg btn-govt-gold">Assign &amp; issue</button>
            </div>
        </form>
    </div>
</div>
@endsection
