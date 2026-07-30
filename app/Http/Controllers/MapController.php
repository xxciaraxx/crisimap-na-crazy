<?php

namespace App\Http\Controllers;

use App\Models\AssistanceType;
use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Support\TagudinBarangays;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapController extends Controller
{
    private const DEFAULT_ASSISTANCE_TYPES = [
        'Medical', 'Food', 'Livelihood', 'Education', 'Transportation', 'Construction Materials', 'Burial',
    ];
    /** Fixed category presentation values shared by the map, legend, and popup. */
    private const CATEGORY_STYLES = [
        'medical' => ['color' => '#E53935', 'icon' => '✚'],
        'burial' => ['color' => '#424242', 'icon' => '✦'],
        'food' => ['color' => '#43A047', 'icon' => '●'],
        'education' => ['color' => '#1E88E5', 'icon' => '◆'],
        'educational' => ['color' => '#1E88E5', 'icon' => '◆'],
        'transportation' => ['color' => '#FB8C00', 'icon' => '➜'],
        'financial' => ['color' => '#8E24AA', 'icon' => '₱'],
        'cash' => ['color' => '#8E24AA', 'icon' => '₱'],
        'livelihood' => ['color' => '#8E24AA', 'icon' => '₱'],
        'construction' => ['color' => '#FDB813', 'icon' => '■'],
        'other' => ['color' => '#FDB813', 'icon' => '•'],
    ];

    public function index()
    {
        $assistanceTypes = AssistanceType::orderBy('name')->get()->map(fn (AssistanceType $type) => array_merge([
            'id' => $type->id,
            'name' => $type->name,
        ], $this->categoryStyle($type->name)));

        $knownNames = $assistanceTypes->pluck('name')->map(fn (string $name) => strtolower($name));
        foreach (self::DEFAULT_ASSISTANCE_TYPES as $name) {
            if (! $knownNames->contains(strtolower($name))) {
                $assistanceTypes->push(array_merge(['id' => 'default-'.strtolower(str_replace(' ', '-', $name)), 'name' => $name], $this->categoryStyle($name)));
            }
        }

        // A legacy record may not yet have an assistance type. Keep it visible
        // under the same gold "Other" category rather than silently dropping it.
        $assistanceTypes->push(array_merge(['id' => 'other', 'name' => 'Other / Miscellaneous'], self::CATEGORY_STYLES['other']));

        return view('map', [
            'barangays' => Barangay::whereIn('code', TagudinBarangays::codes())->orderBy('name')->get(['id', 'name']),
            'assistanceTypes' => $assistanceTypes,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Beneficiary::query()
            ->active()
            ->with([
                'barangay:id,name',
                'assistanceType:id,name',
                'caseRecord:id,case_number,encoded_by',
                'encoder:id,name',
            ]);

        // Social officers can only receive markers belonging to cases they submitted.
        // The restrictions happen in the query, not merely in the popup UI.
        if ($user->role === 'social_officer') {
            $query->whereHas('caseRecord', fn ($caseRecords) => $caseRecords->where('encoded_by', $user->id));
        }

        if ($request->filled('barangay_id')) {
            $query->where('barangay_id', $request->integer('barangay_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(fn ($builder) => $builder
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('reference_code', 'like', '%'.$search.'%'));
        }
        if ($request->filled('from')) {
            $query->whereDate('date_of_assistance', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('date_of_assistance', '<=', $request->date('to'));
        }

        $beneficiaries = $query->whereNotNull('latitude')->whereNotNull('longitude')
            ->orderByDesc('date_of_assistance')->limit(5000)->get([
                'id', 'name', 'reference_code', 'status', 'barangay_id', 'assistance_type_id', 'geocode_precision',
                'case_record_id', 'latitude', 'longitude', 'amount', 'date_of_assistance', 'encoded_by',
            ]);

        return response()->json([
            'beneficiaries' => $beneficiaries->map(function (Beneficiary $beneficiary) {
                return [
                    'id' => $beneficiary->id,
                    'latitude' => $beneficiary->latitude,
                    'longitude' => $beneficiary->longitude,
                    'status' => $beneficiary->status,
                    'geocode_precision' => $beneficiary->geocode_precision ?? 'manual',
                    'date_availed' => $beneficiary->date_of_assistance?->toDateString(),
                    'barangay' => $beneficiary->barangay?->name,
                    'category' => array_merge(['id' => $beneficiary->assistance_type_id ?? 'other', 'name' => $beneficiary->assistanceType?->name ?? 'Other / Miscellaneous'], $this->categoryStyle($beneficiary->assistanceType?->name)),
                    // Only roles entitled to a case record receive identifying case fields.
                    'details' => [
                        'name' => $beneficiary->name,
                        'reference_code' => $beneficiary->reference_code,
                        'amount' => $beneficiary->amount,
                        'case_record_id' => $beneficiary->case_record_id,
                        'case_number' => $beneficiary->caseRecord?->case_number,
                        'social_officer' => $beneficiary->encoder?->name,
                    ],
                ];
            })->values(),
            'summary' => ['count' => $beneficiaries->count()],
        ]);
    }

    public function boundaries(): JsonResponse
    {
        foreach ([public_path('geo/barangay-boundaries.geojson'), storage_path('app/public/geo/barangay-boundaries.geojson')] as $path) {
            if (file_exists($path)) {
                return response()->json(json_decode(file_get_contents($path), true));
            }
        }

        return response()->json(['type' => 'FeatureCollection', 'features' => []]);
    }

    private function categoryStyle(?string $name): array
    {
        $normalised = strtolower((string) $name);
        foreach (self::CATEGORY_STYLES as $keyword => $style) {
            if (str_contains($normalised, $keyword)) {
                return $style;
            }
        }

        return self::CATEGORY_STYLES['other'];
    }
}
