<?php

namespace App\Http\Controllers;

use App\Models\AssistanceType;
use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Support\TagudinBarangays;
use App\Support\DateRangeResolver;
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
        'food' => ['color' => '#43A047', 'icon' => '◉'],
        'education' => ['color' => '#1E88E5', 'icon' => '◆'],
        'educational' => ['color' => '#1E88E5', 'icon' => '◆'],
        'transportation' => ['color' => '#FB8C00', 'icon' => '➜'],
        'financial' => ['color' => '#8E24AA', 'icon' => '₱'],
        'cash' => ['color' => '#8E24AA', 'icon' => '₱'],
        'livelihood' => ['color' => '#8E24AA', 'icon' => '₱'],
        'construction' => ['color' => '#FDB813', 'icon' => '■'],
        'other' => ['color' => '#00897B', 'icon' => '•'],
    ];

    public function index(Request $request)
    {
        $assistanceTypes = AssistanceType::included()
            ->orderBy('name')
            ->get()
            ->map(fn (AssistanceType $type) => array_merge([
            'id' => $type->id,
            'name' => $type->name,
        ], $this->categoryStyle($type->name, $type->color)));

        $knownNames = $assistanceTypes->pluck('name')->map(fn (string $name) => strtolower($name));
        foreach (self::DEFAULT_ASSISTANCE_TYPES as $name) {
            if (! $knownNames->contains(strtolower($name))) {
                $assistanceTypes->push(array_merge(['id' => 'default-'.strtolower(str_replace(' ', '-', $name)), 'name' => $name], $this->categoryStyle($name)));
            }
        }

        return view('map', [
            'barangays' => Barangay::whereIn('code', TagudinBarangays::codes())->orderBy('name')->get(['id', 'name']),
            'assistanceTypes' => $assistanceTypes,
            'fullMap' => $request->boolean('full'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $user = $request->user();
        $canViewSensitiveDetails = $user->hasRole('social_officer', 'focal_person', 'admin', 'super_admin');
        $query = Beneficiary::query()
            ->active()
            ->with([
                'barangay:id,name',
                'assistanceType:id,name,color',
                'encoder:id,name',
                'intake:id,beneficiary_name',
            ]);

        $historyView = $request->input('view') === 'history';
        if ($historyView) {
            $query->whereIn('status', ['Approved', 'Released', 'Completed']);
        }

        if ($request->filled('barangay_id')) {
            $query->where('barangay_id', $request->integer('barangay_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        } elseif (! $historyView) {
            $query->whereIn('status', ['Approved', 'Released', 'Completed']);
        }
        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(fn ($builder) => $builder
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('reference_code', 'like', '%'.$search.'%'));
        }
        $query->withIncludedAssistanceType();
        [$from, $to] = $request->filled('from') || $request->filled('to')
            ? [$request->date('from'), $request->date('to')]
            : (DateRangeResolver::resolve($request->input('period')) ?? [null, null]);
        if ($from) {
            $query->whereDate('date_of_assistance', '>=', $from);
        }
        if ($to) {
            $query->whereDate('date_of_assistance', '<=', $to);
        }

        $missingLocationCount = (clone $query)->where(fn ($query) => $query->whereNull('latitude')->orWhereNull('longitude'))->count();
        $beneficiaries = $query->whereNotNull('latitude')->whereNotNull('longitude')
            ->orderByDesc('date_of_assistance')->limit(5000)->get([
                'id', 'name', 'reference_code', 'status', 'barangay_id', 'assistance_type_id', 'geocode_precision',
                'latitude', 'longitude', 'amount', 'date_of_assistance', 'approved_at', 'encoded_by', 'rejection_reason', 'general_intake_sheet_id',
            ]);

        return response()->json([
            'beneficiaries' => $beneficiaries->map(function (Beneficiary $beneficiary) use ($canViewSensitiveDetails) {
                $locationIsExact = $beneficiary->geocode_precision === 'manual';
                $registeredBeneficiaryName = $beneficiary->intake?->beneficiary_name ?: $beneficiary->name;
                return [
                    'id' => $beneficiary->id,
                    // Social officers can monitor their cases without receiving
                    // a household-level location or personal financial data.
                    'latitude' => ! $canViewSensitiveDetails && $locationIsExact ? round($beneficiary->latitude, 3) : $beneficiary->latitude,
                    'longitude' => ! $canViewSensitiveDetails && $locationIsExact ? round($beneficiary->longitude, 3) : $beneficiary->longitude,
                    'status' => $beneficiary->status,
                    'geocode_precision' => $beneficiary->geocode_precision ?? 'manual',
                    'date_availed' => $beneficiary->date_of_assistance?->toDateString(),
                    'approved_at' => $beneficiary->approved_at?->toDateString(),
                    'rejection_reason' => $beneficiary->rejection_reason,
                    'barangay' => $beneficiary->barangay?->name,
                    'category' => array_merge(['id' => $beneficiary->assistance_type_id, 'name' => $beneficiary->assistanceType->name], $this->categoryStyle($beneficiary->assistanceType->name, $beneficiary->assistanceType->color)),
                    // Only authorized roles receive identifying application fields.
                    'details' => [
                        'beneficiary' => $canViewSensitiveDetails ? $registeredBeneficiaryName : 'Protected beneficiary',
                        'name' => $canViewSensitiveDetails ? $registeredBeneficiaryName : 'Protected beneficiary',
                        'reference_code' => $canViewSensitiveDetails ? $beneficiary->reference_code : null,
                        'amount' => $canViewSensitiveDetails ? $beneficiary->amount : null,
                        'social_officer' => $canViewSensitiveDetails ? $beneficiary->encoder?->name : null,
                    ],
                ];
            })->values(),
            'summary' => ['count' => $beneficiaries->count(), 'missing_location_count' => $missingLocationCount, 'view' => $historyView ? 'history' : 'live'],
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

    private function categoryStyle(?string $name, ?string $color = null): array
    {
        $normalised = strtolower((string) $name);
        foreach (self::CATEGORY_STYLES as $keyword => $style) {
            if (str_contains($normalised, $keyword)) {
                return ['color' => $color ?: $style['color'], 'icon' => $style['icon']];
            }
        }

        return ['color' => $color ?: self::CATEGORY_STYLES['other']['color'], 'icon' => self::CATEGORY_STYLES['other']['icon']];
    }
}
