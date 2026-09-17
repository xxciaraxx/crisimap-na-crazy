<table>
    <thead>
        <tr>
            <th>Reference Code</th>
            <th>Name</th>
            <th>Barangay</th>
            <th>Assistance Type</th>
            <th>Applied</th>
            <th>Amount</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $beneficiary)
            <tr>
                <td>{{ $beneficiary->reference_code ?? 'N/A' }}</td>
                <td>{{ $beneficiary->name }}</td>
                <td>{{ $beneficiary->barangay->name ?? 'N/A' }}</td>
                <td>{{ $beneficiary->assistanceType->name ?? 'N/A' }}</td>
                <td>{{ $beneficiary->date_of_assistance?->format('Y-m-d') ?? '' }}</td>
                <td>{{ (float) ($beneficiary->amount ?? 0) }}</td>
                <td>{{ $beneficiary->status }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
