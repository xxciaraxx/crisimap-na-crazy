<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>CRISIMAP Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #1f2937; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #1565C0; color: white; }
        .summary { display: flex; flex-wrap: wrap; gap: 10px; margin: 12px 0; }
        .summary div { border: 1px solid #ddd; padding: 8px 10px; border-radius: 6px; }
    </style>
</head>
<body>
    <h2>CRISIMAP Report</h2>
    <p>Generated on {{ now()->format('F d, Y') }}</p>
    <div class="summary">
        <div><strong>Records:</strong> {{ $summary['total'] }}</div>
        <div><strong>Total Amount:</strong> PHP {{ number_format($summary['total_amount'], 2) }}</div>
        <div><strong>Average Amount:</strong> PHP {{ number_format($summary['average_amount'], 2) }}</div>
        <div><strong>Release Rate:</strong> {{ $summary['release_rate'] }}%</div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Reference</th>
                <th>Name</th>
                <th>Barangay</th>
                <th>Assistance Type</th>
                <th>Birth Date</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Eligibility</th>
            </tr>
        </thead>
        <tbody>
            @foreach($beneficiaries as $beneficiary)
                <tr>
                    <td>{{ $beneficiary->reference_code ?? 'N/A' }}</td>
                    <td>{{ $beneficiary->name }}</td>
                    <td>{{ $beneficiary->barangay->name ?? '—' }}</td>
                    <td>{{ $beneficiary->assistanceType->name ?? '—' }}</td>
                    <td>{{ $beneficiary->birth_date?->format('M d, Y') ?? '—' }}</td>
                    <td>PHP {{ number_format((float) ($beneficiary->amount ?? 0), 2) }}</td>
                    <td>{{ $beneficiary->status }}</td>
                    <td>{{ $beneficiary->eligibility_status ?? 'Eligible' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
