@extends('layouts.app')
@section('title', 'Audit Logs')

@section('content')
<div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-wrap justify-between items-center gap-4">
    <h2 class="text-lg font-bold text-slate-800">Centralized Visitor Access Logs</h2>
    <a href="{{ route('logs.export') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3.5 py-2 rounded-xl text-xs">
        <i class="fa-solid fa-file-csv"></i> Export CSV
    </a>
</div>

<form method="GET" class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex flex-wrap gap-3 items-center">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, pass #, building..." class="flex-grow min-w-[200px] px-3 py-2 text-xs border border-slate-300 rounded-lg">
    <select name="result" class="px-3 py-2 text-xs border border-slate-300 rounded-lg bg-slate-50">
        <option value="ALL">All Results</option>
        <option value="AUTHORIZED" {{ request('result') === 'AUTHORIZED' ? 'selected' : '' }}>AUTHORIZED</option>
        <option value="UNAUTHORIZED" {{ request('result') === 'UNAUTHORIZED' ? 'selected' : '' }}>UNAUTHORIZED</option>
        <option value="INVALID" {{ request('result') === 'INVALID' ? 'selected' : '' }}>INVALID</option>
    </select>
    <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-xs font-semibold">Filter</button>
</form>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead>
                <tr class="bg-slate-900 text-white uppercase font-bold">
                    <th class="py-3 px-4">Timestamp</th>
                    <th class="py-3 px-4">Visitor</th>
                    <th class="py-3 px-4">Pass #</th>
                    <th class="py-3 px-4">Authorized Bldg</th>
                    <th class="py-3 px-4">Scanned At</th>
                    <th class="py-3 px-4">Result</th>
                    <th class="py-3 px-4">Reason</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $l)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4 font-mono text-slate-500">{{ $l->created_at->format('Y-m-d h:i:s A') }}</td>
                        <td class="py-3 px-4 font-bold">{{ $l->visitor_name_snapshot }}</td>
                        <td class="py-3 px-4 font-mono font-bold">{{ $l->pass_number_snapshot }}</td>
                        <td class="py-3 px-4">{{ $l->authorized_building_snapshot }}</td>
                        <td class="py-3 px-4 font-semibold">{{ $l->scannedBuilding->name ?? '' }}</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 rounded-md border text-[10px] font-black uppercase
                                {{ $l->result === 'AUTHORIZED' ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-red-100 text-red-800 border-red-300' }}">
                                {{ $l->result }}
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