<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBeneficiaryRequest;
use App\Http\Requests\UpdateBeneficiaryRequest;
use App\Models\ActivityLog;
use App\Models\AssistanceType;
use App\Models\Attachment;
use App\Models\Barangay;
use App\Models\Beneficiary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class BeneficiaryController extends Controller
{
    public function index(Request $request)
    {
        $query = Beneficiary::query()->with(['barangay', 'assistanceType', 'encoder']);

        if ($request->filled('barangay_id')) {
            $query->where('barangay_id', $request->barangay_id);
        }
        if ($request->filled('assistance_type_id')) {
            $query->where('assistance_type_id', $request->assistance_type_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }
        if ($request->filled('from')) {
            $query->whereDate('date_of_assistance', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('date_of_assistance', '<=', $request->to);
        }

        $beneficiaries = $query->latest()->paginate(15)->withQueryString();
        $barangays = Barangay::orderBy('name')->get();
        $assistanceTypes = AssistanceType::orderBy('name')->get();

        return view('beneficiaries.index', compact('beneficiaries', 'barangays', 'assistanceTypes'));
    }

    public function create()
    {
        return view('beneficiaries.create', [
            'barangays' => Barangay::orderBy('name')->get(),
            'assistanceTypes' => AssistanceType::orderBy('name')->get(),
        ]);
    }

    public function store(StoreBeneficiaryRequest $request)
    {
        $data = $request->validated();
        $data['encoded_by'] = auth()->id();
        $data['latitude'] = $data['latitude'] ?? $this->geocodeAddress($request->address);
        $data['longitude'] = $data['longitude'] ?? $this->geocodeAddress($request->address, 'longitude');

        $beneficiary = Beneficiary::create($data);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('attachments', 'public');
            $beneficiary->attachments()->create([
                'name' => $request->file('attachment')->getClientOriginalName(),
                'path' => $path,
            ]);
        }

        $this->logActivity('created', 'Created beneficiary record for '.$beneficiary->name, $beneficiary);

        return redirect()->route('beneficiaries.index')->with('success', 'Beneficiary record created.');
    }

    public function show(Beneficiary $beneficiary)
    {
        $beneficiary->load(['barangay', 'assistanceType', 'attachments', 'encoder']);

        return view('beneficiaries.show', compact('beneficiary'));
    }

    public function edit(Beneficiary $beneficiary)
    {
        return view('beneficiaries.edit', [
            'beneficiary' => $beneficiary,
            'barangays' => Barangay::orderBy('name')->get(),
            'assistanceTypes' => AssistanceType::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateBeneficiaryRequest $request, Beneficiary $beneficiary)
    {
        $data = $request->validated();
        if (empty($data['latitude']) || empty($data['longitude'])) {
            $data['latitude'] = $data['latitude'] ?? $this->geocodeAddress($request->address);
            $data['longitude'] = $data['longitude'] ?? $this->geocodeAddress($request->address, 'longitude');
        }

        $beneficiary->update($data);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('attachments', 'public');
            $beneficiary->attachments()->create([
                'name' => $request->file('attachment')->getClientOriginalName(),
                'path' => $path,
            ]);
        }

        $this->logActivity('updated', 'Updated beneficiary record for '.$beneficiary->name, $beneficiary);

        return redirect()->route('beneficiaries.show', $beneficiary)->with('success', 'Beneficiary record updated.');
    }

    public function destroy(Beneficiary $beneficiary)
    {
        $beneficiary->delete();
        $this->logActivity('deleted', 'Deleted beneficiary record for '.$beneficiary->name, $beneficiary);

        return redirect()->route('beneficiaries.index')->with('success', 'Beneficiary record deleted.');
    }

    private function geocodeAddress(?string $address, string $field = 'latitude')
    {
        if (blank($address)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'CRISIMAP/1.0',
            ])->get('https://nominatim.openstreetmap.org/search', [
                'format' => 'jsonv2',
                'q' => $address.', Tagudin, Ilocos Sur, Philippines',
                'limit' => 1,
            ]);

            if ($response->successful() && ! empty($response->json()[0])) {
                $result = $response->json()[0];

                return $field === 'longitude' ? (float) $result['lon'] : (float) $result['lat'];
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    private function logActivity(string $action, string $description, Beneficiary $beneficiary): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => Beneficiary::class,
            'subject_id' => $beneficiary->id,
        ]);
    }
}
