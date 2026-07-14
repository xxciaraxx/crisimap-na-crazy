<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use App\Models\AssistanceType;
use App\Models\Barangay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MapController extends Controller
{
    public function index(Request $request)
    {
        return view('map', [
            'barangays' => Barangay::orderBy('name')->get(),
            'assistanceTypes' => AssistanceType::orderBy('name')->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Beneficiary::query()
            ->active()
            ->with(['barangay:id,name', 'assistanceType:id,name,color']);

        if ($request->filled('barangay_id')) {
            $query->where('barangay_id', $request->integer('barangay_id'));
        }
        if ($request->filled('assistance_type_id')) {
            $query->where('assistance_type_id', $request->integer('assistance_type_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('search')) {
            $search = trim($request->string('search'));
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('reference_code', 'like', '%'.$search.'%')
                    ->orWhere('contact', 'like', '%'.$search.'%');
            });
        }
        if ($request->filled('from')) {
            $query->whereDate('date_of_assistance', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('date_of_assistance', '<=', $request->date('to'));
        }

        $beneficiaries = $query
            ->select([
                'id',
                'name',
                'reference_code',
                'status',
                'barangay_id',
                'assistance_type_id',
                'address',
                'latitude',
                'longitude',
                'amount',
                'date_of_assistance',
            ])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->limit(5000)
            ->get();

        return response()->json([
            'beneficiaries' => $beneficiaries,
            'summary' => [
                'count' => $beneficiaries->count(),
                'heatmap' => $beneficiaries->map(function ($beneficiary) {
                    return [
                        'lat' => (float) $beneficiary->latitude,
                        'lng' => (float) $beneficiary->longitude,
                        'value' => $beneficiary->amount ? (float) max(1, min(5, ceil($beneficiary->amount / 2000))) : 1,
                    ];
                }),
            ],
        ]);
    }

    public function boundaries(): JsonResponse
    {
        $paths = [
            storage_path('app/public/geo/barangay-boundaries.geojson'),
            public_path('geo/barangay-boundaries.geojson'),
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return response()->json(json_decode(file_get_contents($path), true));
            }
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => [],
        ]);
    }
}
