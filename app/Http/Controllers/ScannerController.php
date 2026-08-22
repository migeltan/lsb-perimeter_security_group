<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\ScanLog;
use App\Models\VisitorPass;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ScannerController extends Controller
{
    public function index()
    {
        $buildings = Building::orderBy('name')->get();
        $passes = VisitorPass::with('building')->orderBy('building_id')->orderBy('pass_number')->get();
        $recentLogs = ScanLog::with(['visitorPass', 'scannedBuilding'])->latest()->limit(50)->get();

        return view('scanner.index', compact('buildings', 'passes', 'recentLogs'));
    }

    public function scan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => 'required|string',
            'scanned_building_id' => 'required|exists:buildings,id',
        ]);

        $scannerBuilding = Building::findOrFail($data['scanned_building_id']);
        $pass = VisitorPass::with('building')->where('qr_token', $data['token'])->first();

        $result = 'INVALID';
        $reason = 'QR Token payload not recognized in central database.';
        $visitorName = 'Unknown / Unregistered';
        $passNumber = '----';
        $authorizedBuildingName = 'None';
        $colorHex = '#64748b';

        if ($pass) {
            $visitorName = $pass->visitor_name ?: 'Unassigned Card';
            $passNumber = $pass->pass_number;
            $authorizedBuildingName = $pass->building->name;
            $colorHex = $pass->building->color_hex;

            if ($pass->status === 'expired') {
                $result = 'EXPIRED';
                $reason = 'Visitor pass status marked as EXPIRED.';
            } elseif ($pass->status === 'revoked') {
                $result = 'REVOKED';
                $reason = 'Visitor pass is REVOKED by Security.';
            } elseif ($pass->building_id !== $scannerBuilding->id) {
                $result = 'UNAUTHORIZED';
                $reason = "BUILDING MISMATCH! Pass is authorized ONLY for [{$authorizedBuildingName}], but scanned at [{$scannerBuilding->name}].";
            } else {
                $result = 'AUTHORIZED';
                $reason = 'Access Granted - Building Match Validated';
            }
        }

        $log = ScanLog::create([
            'visitor_pass_id' => $pass?->id,
            'qr_token_scanned' => $data['token'],
            'scanned_building_id' => $scannerBuilding->id,
            'visitor_name_snapshot' => $visitorName,
            'pass_number_snapshot' => $passNumber,
            'authorized_building_snapshot' => $authorizedBuildingName,
            'result' => $result,
            'reason' => $reason,
        ]);

        return response()->json([
            'result' => $result,
            'reason' => $reason,
            'visitor_name' => $visitorName,
            'pass_number' => $passNumber,
            'authorized_building' => $authorizedBuildingName,
            'scanned_building' => $scannerBuilding->name,
            'color_hex' => $colorHex,
            'timestamp' => $log->created_at->format('h:i:s A'),
        ]);
    }
}