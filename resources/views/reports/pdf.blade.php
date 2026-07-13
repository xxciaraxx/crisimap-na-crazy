<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>CRISIMAP Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #1565C0; color: white; }
    </style>
</head>
<body>
    <h2>CRISIMAP Beneficiary Report</h2>
    <p>Generated on {{ now()->format('F d, Y') }}</p>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Barangay</th>
                <th>Assistance Type</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($beneficiaries as $beneficiary)
                <tr>
                    <td>{{ $beneficiary->name }}</td>
                    <td>{{ $beneficiary->barangay->name ?? '—' }}</td>
                    <td>{{ $beneficiary->assistanceType->name ?? '—' }}</td>
                    <td>{{ $beneficiary->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
