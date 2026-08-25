@extends('layouts.app')
@section('title', 'Audit Logs')

@section('content')
<div class="hero-govt flex items-center gap-5">
    <div class="hero-logo-badge hidden sm:flex">
        <img src="{{ asset('images/lsb-icon.png') }}" alt="">
    </div>
    <div>
        <p class="eyebrow">Internal Security Group &middot; Audit trail</p>
        <h1>Centralized Visitor Access Logs</h1>
        <p class="lead">Every scan event, authorized or denied, recorded with timestamp, pass, and reason.</p>
    </div>
</div>

<div class="card-govt card-ribbon p-5 shadow-sm flex flex-wrap justify-between items-center gap-4" style="--ribbon-color: var(--brand-gold)">
    <div>
        <span class="tag-pill tag-pill-gold">Export</span>
        <h2 class="text-lg font-bold text-slate-800 mt-1">Log records</h2>
    </div>
    <a href="{{ route('logs.export') }}" class="btn-govt-success font-bold px-3.5 py-2 rounded-xl text-xs">
        <i class="fa-solid fa-file-csv"></i> Export CSV
    </a>
</div>

<form method="GET" class="card-govt p-4 shadow-sm flex flex-wrap gap-3 items-center">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, pass #, building..." class="flex-grow min-w-[200px] px-3 py-2 text-xs border border-slate-300 rounded-lg">
    <select name="result" class="px-3 py-2 text-xs border border-slate-300 rounded-lg bg-slate-50">
        <option value="ALL">All results</option>
        <option value="AUTHORIZED" {{ request('result') === 'AUTHORIZED' ? 'selected' : '' }}>Authorized</option>
        <option value="UNAUTHORIZED" {{ request('result') === 'UNAUTHORIZED' ? 'selected' : '' }}>Unauthorized</option>
        <option value="INVALID" {{ request('result') === 'INVALID' ? 'selected' : '' }}>Invalid</option>
    </select>
    <button type="submit" class="btn-govt-primary px-4 py-2 rounded-lg text-xs">Filter</button>
</form>

<div class="card-govt shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table-govt w-full text-left text-xs">
            <thead>
                <tr>
                    <th class="py-3 px-4">Timestamp</th>
                    <th class="py-3 px-4">Visitor</th>
                    <th class="py-3 px-4">Pass #</th>
                    <th class="py-3 px-4">Authorized bldg</th>
                    <th class="py-3 px-4">Scanned at</th>
                    <th class="py-3 px-4">Result</th>
                    <th class="py-3 px-4">Reason</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $l)
                    <tr>
                        <td class="py-3 px-4 font-mono text-slate-500">{{ $l->created_at->format('Y-m-d h:i:s A') }}</td>
                        <td class="py-3 px-4 font-bold">{{ $l->visitor_name_snapshot }}</td>
                        <td class="py-3 px-4 font-mono font-bold">{{ $l->pass_number_snapshot }}</td>
                        <td class="py-3 px-4">{{ $l->authorized_building_snapshot }}</td>
                        <td class="py-3 px-4 font-semibold">{{ $l->scannedBuilding->name ?? '' }}</td>
                        <td class="py-3 px-4">
                            <span class="badge-status {{ $l->result === 'AUTHORIZED' ? 'badge-authorized' : 'badge-denied' }}">
                                {{ ucfirst(strtolower($l->result)) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-slate-600 truncate max-w-xs" title="{{ $l->reason }}">{{ $l->reason }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-slate-400">No scan events recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="px-2">{{ $logs->links() }}</div>
@endsection
