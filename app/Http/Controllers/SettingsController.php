<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssistanceTypeRequest;
use App\Http\Requests\StoreBarangayRequest;
use App\Models\ActivityLog;
use App\Models\AssistanceType;
use App\Models\Barangay;
use App\Models\OfficialSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'barangays' => Barangay::orderBy('name')->get(),
            'assistanceTypes' => AssistanceType::included()->orderBy('name')->get(),
            'officialSettings' => OfficialSetting::values(),
        ]);
    }

    public function updateOfficialSettings(Request $request)
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($unknownKeys = array_diff(array_keys($data['settings']), OfficialSetting::KEYS)) {
            throw ValidationException::withMessages([
                'settings' => 'Unsupported document setting: '.implode(', ', $unknownKeys),
            ]);
        }
        foreach ($data['settings'] as $key => $value) {
            OfficialSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        ActivityLog::create(['user_id' => auth()->id(), 'action' => 'updated', 'description' => 'Updated official document settings', 'subject_type' => OfficialSetting::class, 'subject_id' => null]);
        return back()->with('success', 'Official document settings updated. Finalized snapshots remain unchanged.');
    }

    public function storeBarangay(StoreBarangayRequest $request)
    {
        $barangay = Barangay::create($request->validated());
        $this->logActivity('created', 'Added barangay '.$barangay->name, $barangay);

        return back()->with('success', 'Barangay added.');
    }

    public function storeAssistanceType(StoreAssistanceTypeRequest $request)
    {
        $type = AssistanceType::create($request->validated());
        $this->logActivity('created', 'Added assistance type '.$type->name, $type);

        return back()->with('success', 'Assistance type added.');
    }

    public function destroyBarangay(Barangay $barangay)
    {
        if ($barangay->beneficiaries()->exists() || $barangay->caseRecords()->exists() || $barangay->intakes()->exists()) {
            return back()->with('error', 'This barangay is in use and cannot be removed.');
        }

        $name = $barangay->name;
        $barangay->delete();
        $this->logActivity('deleted', 'Removed barangay '.$name, $barangay);
        return back()->with('success', 'Barangay removed.');
    }

    public function destroyAssistanceType(AssistanceType $assistanceType)
    {
        if ($assistanceType->beneficiaries()->exists()) {
            return back()->with('error', 'This assistance type is in use and cannot be removed.');
        }

        $name = $assistanceType->name;
        $assistanceType->delete();
        $this->logActivity('deleted', 'Removed assistance type '.$name, $assistanceType);
        return back()->with('success', 'Assistance type removed.');
    }

    private function logActivity(string $action, string $description, $subject): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
        ]);
    }
}
