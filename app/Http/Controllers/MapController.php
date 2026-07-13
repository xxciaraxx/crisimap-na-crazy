<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use App\Models\Barangay;
use Illuminate\Http\Request;

class MapController extends Controller
{
    public function index(Request $request)
    {
        $barangays = Barangay::orderBy('name')->get();
        $query = Beneficiary::with(['barangay', 'assistanceType']);

        if ($request->filled('barangay_id')) {
            $query->where('barangay_id', $request->barangay_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $beneficiaries = $query->get();

        return view('map', compact('beneficiaries', 'barangays'));
    }
}
