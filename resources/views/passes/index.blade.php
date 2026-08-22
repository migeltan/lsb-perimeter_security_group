@extends('layouts.app')
@section('title', 'Visitor Pass Management')

@section('content')
<div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-wrap justify-between items-center gap-4">
    <div>
        <h2 class="text-lg font-bold text-slate-800">Visitor Pass Registry</h2>
        <p class="text-xs text-slate-500">5 passes per building — assign a visitor to issue and print.</p>
    </div>
    <button onclick="document.getElementById('registerModal').classList.remove('hidden')" class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold px-4 py-2 rounded-xl text-xs">
        <i class="fa-solid fa-user-plus"></i> Register Visitor & Issue Pass
    </button>
</div>

<div class="flex overflow-x-auto gap-2 pb-2">
    <a href="{{ route('passes.index') }}" class="px-3 py-1.5 rounded-xl text-xs font-bold {{ !request('building') ? 'bg-slate-900 text-white' : 'bg-white border text-slate-700' }}">All Buildings</a>
    @foreach ($buildings as $b)
        <a href="{{ route('passes.index', ['building' => $b->code]) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 {{ request('building') === $b->code ? 'bg-slate-900 text-white' : 'bg-white border text-slate-700' }}">
            <span class="w-2.5 h-2.5 rounded-full" style="background:{{ $b->color_hex }}"></span> {{ $b->name }}
        </a>
    @endforeach
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    @foreach ($passes as $p)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col justify-between">
            <div class="p-4 border-b border-slate-100 flex justify-between items-start">
                <div>
                    <span class="text-[10px] font-bold uppercase text-slate-400">House of Reps</span>
                    <div class="font-extrabold text-slate-900 text-base">{{ $p->building->name }}</div>
                    <div class="text-xs font-bold text-slate-500">VISITOR PASS</div>
                </div>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $p->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                    {{ strtoupper($p->status) }}
                </span>
            </div>
            <div class="p-4 space-y-2 text-xs">
                <div class="flex justify-between bg-slate-50 p-2 rounded-lg font-mono">
                    <span class="text-slate-400">Pass #:</span>
                    <span class="font-bold text-slate-800 text-sm">{{ $p->pass_number }}</span>
                </div>
                <div>
                    <span class="text-slate-400 text-[10px] uppercase font-bold">Visitor:</span>
                    <div class="font-bold text-slate-800 truncate">{{ $p->visitor_name ?: 'Unassigned' }}</div>
                </div>
            </div>
            <div class="px-4 py-2 text-white flex justify-between items-center" style="background:{{ $p->building->color_hex }}">
                <div class="text-xs font-bold">{{ $p->building->color_name }} Badge</div>
                <a href="{{ route('passes.show', $p) }}" class="bg-white/20 hover:bg-white/30 text-white px-2.5 py-1 rounded text-xs font-bold">
                    <i class="fa-solid fa-qrcode"></i> View QR
                </a>
            </div>
        </div>
    @endforeach
</div>

<div id="registerModal" class="fixed inset-0 bg-slate-900/60 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
        <h3 class="font-bold text-slate-800 text-base">Register Visitor & Issue Pass</h3>
        <form method="POST" action="{{ route('passes.register') }}" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Visitor Full Name *</label>
                <input type="text" name="visitor_name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Government ID / Reference *</label>
                <input type="text" name="id_ref" required class="w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Purpose of Visit *</label>
                <input type="text" name="purpose" required class="w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Select Pass *</label>
                <select name="pass_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-slate-50 font-mono">
                    @foreach ($passes as $p)
                        <option value="{{ $p->id }}">[{{ $p->building->name }}] Pass {{ $p->pass_number }} - ({{ $p->visitor_name ? 'Assigned: '.$p->visitor_name : 'AVAILABLE' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('registerModal').classList.add('hidden')" class="px-4 py-2 rounded-lg bg-slate-200 text-slate-700 font-semibold">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-amber-500 text-slate-950 font-bold">Assign & Issue</button>
            </div>
        </form>
    </div>
</div>
@endsection