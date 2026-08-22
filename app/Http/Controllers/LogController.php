<?php

namespace App\Http\Controllers;

use App\Models\ScanLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = ScanLog::with(['scannedBuilding']);

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

        $logs = $query->latest()->paginate(50)->withQueryString();

        return view('logs.index', compact('logs'));
    }

    public function export(): StreamedResponse
    {
        $logs = ScanLog::latest()->get();

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
}