<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\VisitorPass;
use Illuminate\Http\Request;

class PassController extends Controller
{
    public function index(Request $request)
    {
        $buildings = Building::orderBy('name')->get();

        $query = VisitorPass::with('building');
        if ($request->filled('building')) {
            $query->whereHas('building', fn ($q) => $q->where('code', $request->building));
        }
        $passes = $query->orderBy('building_id')->orderBy('pass_number')->get();

        return view('passes.index', compact('buildings', 'passes'));
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'pass_id' => 'required|exists:visitor_passes,id',
            'visitor_name' => 'required|string|max:255',
            'id_ref' => 'required|string|max:255',
            'purpose' => 'required|string|max:255',
        ]);

        $pass = VisitorPass::findOrFail($data['pass_id']);
        $pass->update([
            'visitor_name' => $data['visitor_name'],
            'id_ref' => $data['id_ref'],
            'purpose' => $data['purpose'],
            'status' => 'active',
            'issued_at' => now(),
        ]);

        return redirect()->route('passes.index')->with('success', "Pass assigned to {$pass->visitor_name}.");
    }

    public function show(VisitorPass $pass)
    {
        $pass->load('building');
        return view('passes.show', compact('pass'));
    }
}