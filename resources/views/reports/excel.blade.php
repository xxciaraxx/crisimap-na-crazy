<table>
    <thead>
        <tr>
            <th>Reference Code</th>
            <th>Name</th>
            <th>Barangay</th>
            <th>Assistance Type</th>
            <th>Birth Date</th>
            <th>Date of Assistance</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Eligibility Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $beneficiary)
            <tr>
                <td>{{ $beneficiary->reference_code ?? 'N/A' }}</td>
                <td>{{ $beneficiary->name }}</td>
                <td>{{ $beneficiary->barangay->name ?? 'N/A' }}</td>
                <td>{{ $beneficiary->assistanceType->name ?? 'N/A' }}</td>
                <td>{{ $beneficiary->birth_date?->format('Y-m-d') ?? '' }}</td>
                <td>{{ $beneficiary->date_of_assistance?->format('Y-m-d') ?? '' }}</td>
                <td>{{ (float) ($beneficiary->amount ?? 0) }}</td>
                <td>{{ $beneficiary->status }}</td>
                <td>{{ $beneficiary->eligibility_status ?? 'Eligible' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
