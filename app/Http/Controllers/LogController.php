<?php

namespace App\Http\Controllers;

use App\Models\Building; // ASSUMPTION: adjust to your actual Building model namespace/path
use App\Models\ScanLog;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $buildings = Building::orderBy('name')->get(); // same source used by scanner/index.blade.php's $buildings

        $logs = $this->applyFilters(ScanLog::with(['scannedBuilding']), $request)
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('logs.index', compact('logs', 'buildings'));
    }

    public function export(Request $request): StreamedResponse
    {
        $logs = $this->applyFilters(ScanLog::with(['scannedBuilding']), $request)
            ->latest()
            ->get();

        $callback = function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Timestamp', 'Visitor Name', 'Pass Number', 'Authorized Building', 'Scanned Building', 'Result', 'Reason']);
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->visitor_name_snapshot,
                    $log->pass_number_snapshot,
                    $log->authorized_building_snapshot,
                    $log->scannedBuilding->name ?? '',
                    $log->result,
                    $log->reason,
                ]);
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, 'scan_logs_' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Option A — delete logs whose created_at falls within [start_date, end_date] (inclusive).
     */
    public function purgeRange(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $count = ScanLog::whereBetween('created_at', [
            $validated['start_date'] . ' 00:00:00',
            $validated['end_date'] . ' 23:59:59',
        ])->delete();

        return redirect()->route('logs.index')
            ->with('success', "Purged {$count} log(s) between {$validated['start_date']} and {$validated['end_date']}.");
    }

    /**
     * Option B — delete every log. Requires the literal string "PURGE" typed by the user.
     * Uses delete() rather than truncate() to respect FK constraints on scanned_building_id.
     */
    public function purgeAll(Request $request)
    {
        $request->validate([
            'confirm' => 'required|string',
        ]);

        if (strtoupper(trim($request->confirm)) !== 'PURGE') {
            return redirect()->route('logs.index')
                ->with('error', 'Confirmation keyword did not match. No logs were deleted.');
        }

        $count = ScanLog::query()->count();
        ScanLog::query()->delete();

        return redirect()->route('logs.index')
            ->with('success', "All {$count} log(s) were permanently deleted.");
    }

    /**
     * Shared search/result/building filtering used by both index() and export(),
     * so the CSV always matches what's on screen.
     */
    private function applyFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('visitor_name_snapshot', 'like', "%{$s}%")
                  ->orWhere('pass_number_snapshot', 'like', "%{$s}%")
                  ->orWhere('authorized_building_snapshot', 'like', "%{$s}%");
            });
        }

        if ($request->filled('result') && $request->result !== 'ALL') {
            $query->where('result', $request->result);
        }

        if ($request->filled('building') && $request->building !== 'ALL') {
            $query->where('scanned_building_id', $request->building);
        }

        return $query;
    }
}