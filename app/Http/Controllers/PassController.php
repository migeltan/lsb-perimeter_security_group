<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\VisitorPass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PassController extends Controller
{
    public function index(Request $request)
    {
        $buildings = Building::orderBy('name')->get();

        $query = VisitorPass::with(['building', 'buildings']);
        if ($request->filled('building')) {
            $query->whereHas('building', fn ($q) => $q->where('code', $request->building));
        }
        $passes = $query->orderBy('building_id')->orderBy('pass_number')->get();

        return view('passes.index', compact('buildings', 'passes'));
    }

    public function register(Request $request)
    {
        $passType = $request->input('pass_type', 'single');

        if ($passType === 'multi') {
            return $this->registerMultiBuilding($request);
        }
        $data = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'visitor_name' => 'required|string|max:255',
            'id_type' => 'required|string|max:255',
            'id_ref' => 'required|string|max:255',
            'purpose' => 'required|string|max:255',
        ]);

        $pass = VisitorPass::where('building_id', $data['building_id'])
            ->where('is_multi_building', false)
            ->whereNull('visitor_name')
            ->orderBy('pass_number')
            ->first();

        if (! $pass) {
            return back()->withErrors('No available passes left for that building. Please choose another building.')->withInput();
        }

        $pass->update([
            'visitor_name' => $data['visitor_name'],
            'id_ref' => $data['id_ref'],
            'id_type' => $data['id_type'],
            'purpose' => $data['purpose'],
            'status' => 'active',
            'issued_at' => now(),
            'photo_path' => $this->storeVisitorPhoto($request) ?? $pass->photo_path,
        ]);

        return redirect()->route('passes.index')->with('success', "Pass assigned to {$pass->visitor_name}.");
    }

    public function updateBuildings(Request $request, VisitorPass $pass)
    {
        abort_unless($pass->is_multi_building, 404);

        $data = $request->validate([
            'building_ids' => 'required|array|min:2',
            'building_ids.*' => 'exists:buildings,id',
        ]);

        if ($pass->current_building_id && ! in_array($pass->current_building_id, $data['building_ids'])) {
            return back()->withErrors(
                "Cannot remove {$pass->currentBuilding->name} — visitor is currently checked in there. Scan them out first."
            );
        }

        $pass->buildings()->sync($data['building_ids']);

        return redirect()->route('passes.index')
            ->with('success', "Updated authorized buildings for Pass #{$pass->pass_number}.");
    }

    /**
     * Multiple Access passes share one dedicated "MULTI" building slot
     * (nominal/primary building_id — the pivot table is the real source of
     * authorization truth). Unlike single-building passes, they aren't
     * pre-seeded in bulk ahead of time — but once a pass_number/qr_token is
     * minted, it's permanent (these get printed onto physical PVC cards),
     * so reassigning a visitor must NEVER generate a new pass_number/token
     * for an existing card. Instead: reuse the first unassigned Multi pass
     * if one exists (i.e. was previously unassigned via unassign()), and
     * only mint a brand new pass_number/qr_token — meaning a brand new
     * physical card needs to be printed — when none are free.
     */
    private function registerMultiBuilding(Request $request)
    {
        $data = $request->validate([
            'visitor_name' => 'required|string|max:255',
            'id_type' => 'required|string|max:255',
            'id_ref' => 'required|string|max:255',
            'purpose' => 'required|string|max:255',
            'building_ids' => 'required|array|min:2',
            'building_ids.*' => 'exists:buildings,id',
        ]);

        $multiBuildingId = Building::where('code', 'MULTI')->value('id');

        // Reuse an existing empty Multi pass (same printed QR) if one's free.
        $pass = VisitorPass::where('is_multi_building', true)
            ->whereNull('visitor_name')
            ->first();

        if (! $pass) {
            $nextNumber = (int) (VisitorPass::query()
                ->where('building_id', $multiBuildingId)
                ->selectRaw('MAX(CAST(pass_number AS UNSIGNED)) as max_num')
                ->first()
                ?->max_num ?? 0) + 1;

            $passNumber = str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);

            $pass = VisitorPass::create([
                'building_id' => $multiBuildingId,
                'pass_number' => $passNumber,
                'qr_token' => "PASS-MULTI-{$passNumber}",
                'is_multi_building' => true,
            ]);
        }
                $pass->update([
            'visitor_name' => $data['visitor_name'],
            'id_ref' => $data['id_ref'],
            'id_type' => $data['id_type'],
            'purpose' => $data['purpose'],
            'status' => 'active',
            'issued_at' => now(),
            'photo_path' => $this->storeVisitorPhoto($request) ?? $pass->photo_path,
        ]);

        $pass->buildings()->sync($data['building_ids']);
        $buildingCount = count($data['building_ids']);

        return redirect()->route('passes.index')
            ->with('success', "Multiple Access pass #{$pass->pass_number} issued to {$pass->visitor_name} for {$buildingCount} buildings.");
    }

    public function show(VisitorPass $pass)
    {
        $pass->load(['building', 'buildings']);
        return view('passes.show', compact('pass'));
    }
    
        public function unassign(VisitorPass $pass)
    {
        if ($pass->photo_path) {
            Storage::disk('public')->delete($pass->photo_path);
        }

        $pass->update([
            'visitor_name' => null,
            'id_ref' => null,
            'id_type' => null,
            'purpose' => null,
            'status' => 'available',
            'issued_at' => null,
            'photo_path' => null,
        ]);

        if ($pass->is_multi_building) {
            $pass->buildings()->detach();
        }
        return redirect()->route('passes.index')
            ->with('success', "Pass #{$pass->pass_number} unassigned and returned to available stock.");
    }

    /**
     * Decode and store the base64 webcam capture sent from the Register
     * modal. Returns the storage-relative path, or null if no photo was
     * captured (e.g. staff skipped it) — callers fall back to the pass's
     * existing photo_path in that case so a re-registration never wipes a
     * photo that wasn't retaken.
     */
    private function storeVisitorPhoto(Request $request): ?string
    {
        if (! $request->filled('photo_data')) {
            return null;
        }

        $imageData = $request->input('photo_data');
        $imageData = substr($imageData, strpos($imageData, ',') + 1);
        $imageData = base64_decode($imageData);

        $filename = 'visitor-photos/' . uniqid('visitor_') . '.jpg';
        Storage::disk('public')->put($filename, $imageData);

        return $filename;
    }
}