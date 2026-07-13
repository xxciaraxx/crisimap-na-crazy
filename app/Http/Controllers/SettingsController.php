<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssistanceTypeRequest;
use App\Http\Requests\StoreBarangayRequest;
use App\Models\ActivityLog;
use App\Models\AssistanceType;
use App\Models\Barangay;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'barangays' => Barangay::orderBy('name')->get(),
            'assistanceTypes' => AssistanceType::orderBy('name')->get(),
        ]);
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
        $barangay->delete();
        return back()->with('success', 'Barangay removed.');
    }

    public function destroyAssistanceType(AssistanceType $assistanceType)
    {
        $assistanceType->delete();
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
