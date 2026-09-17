<?php

namespace App\Http\Controllers;

use App\Exports\BeneficiaryBackupExport;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DataBackupController extends Controller
{
    public function index()
    {
        return view('data-backup.index');
    }

    public function export(Request $request)
    {
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'beneficiaries_exported',
            'description' => 'Exported a complete beneficiary data backup.',
        ]);

        return Excel::download(
            new BeneficiaryBackupExport,
            'crisimap-beneficiary-backup-'.now()->format('Y-m-d_His').'.xlsx'
        );
    }
}
