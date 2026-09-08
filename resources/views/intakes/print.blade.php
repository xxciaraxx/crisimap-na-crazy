@php
    $client = $intake?->client_name;
    $person = $beneficiary?->name ?? $intake?->beneficiary_name ?? $client;
    $date = $fields['date'] ?? ($intake?->intake_date?->format('F d, Y') ?? now()->format('F d, Y'));
    $amount = $beneficiary?->amount ?? $intake?->requested_amount ?? 0;
    $assistance = $beneficiary?->assistanceType?->name ?? $intake?->purpose_of_assistance ?? '';
    $checked = fn ($value) => $value ? '&#x2611;' : '&#x2610;';
    $clientSectors = (array) ($intake?->client_sectors ?? []);
    $incomeResources = (array) ($intake?->income_resources ?? []);
    $submittedDocs = (array) ($intake?->supporting_documents ?? []);
    $members = collect($intake?->family_composition ?? [])
        ->map(fn ($member) => is_array($member) ? (object) $member : $member)
        ->take(6);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 portrait; margin: 6mm 8mm 5mm; }
        body {
            margin: 0;
            font-family: Calibri, Carlito, Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.15;
            color: #111;
        }
        * { box-sizing: border-box; }
        .sheet {
            width: 194mm;
            min-height: 286mm;
            margin: 0 auto;
        }
        .letterhead {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
            table-layout: fixed;
            background: #fff;
        }
        .letterhead td {
            height: 74px;
            padding: 0;
            vertical-align: middle;
        }
        .letterhead-mark {
            text-align: center;
        }
        .letterhead-center {
            text-align: center;
            line-height: 1.2;
        }
        .letterhead td:first-child { width: 25%; }
        .letterhead td:nth-child(2) { width: 50%; }
        .letterhead td:last-child { width: 25%; }
        .letterhead-marks { display: flex; align-items: center; justify-content: space-evenly; gap: 8px; }
        .logo {
            display: block;
            height: 64px;
            margin: 0 auto;
            width: auto;
            object-fit: contain;
        }
        .letterhead .tagudin-mark { height: 64px; }
        .letterhead .asap-mark { height: 50px; max-width: 96px; }
        .letterhead .small {
            font-size: 9.5px;
        }
        .letterhead .municipality {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 1px;
        }
        .letterhead-office {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            line-height: 1.05;
            margin: 0 0 2px;
            text-transform: uppercase;
        }
        .letterhead-rule {
            border-bottom: 1px solid #111;
            margin-top: 0;
        }
        .form {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .form td,
        .form th {
            border: 1px solid #111;
            padding: 3px 4px;
            vertical-align: top;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: .2px;
            text-align: center;
            padding: 3px 0;
        }
        .field {
            border-bottom: 1px solid #111;
            display: inline-block;
            min-width: 100px;
            padding: 0 2px;
            line-height: 1.15;
        }
        .field-wide { min-width: 320px; }
        .field-medium { min-width: 180px; }
        .field-small { min-width: 70px; }
        .field-tiny { min-width: 40px; }
        .tight { line-height: 1.15; }
        .mini {
            font-size: 7.5px;
            color: #444;
            line-height: 1.1;
        }
        .subtle {
            font-size: 8px;
        }
        .family-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .family-grid th,
        .family-grid td {
            border: 1px solid #111;
            padding: 2px 3px;
            vertical-align: top;
        }
        .family-grid th {
            background: #f3f3f3;
            font-size: 8.5px;
        }
        .family-row td {
            height: 19px;
        }
        .signature-line {
            border-top: 1px solid #111;
            width: 220px;
            margin: 0 auto;
            padding-top: 2px;
            text-align: center;
            font-size: 8.5px;
        }
        .assessment {
            min-height: 58px;
            white-space: pre-line;
            text-align: justify;
            line-height: 1.35;
        }
        .preview-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            max-width: 794px;
            margin: 0 auto 14px;
            padding: 10px 0;
            font-family: Arial, Helvetica, sans-serif;
        }
        .preview-actions a,
        .preview-actions button {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
            padding: 8px 12px;
            font-size: 13px;
            text-decoration: none;
            cursor: pointer;
        }
        .preview-actions button { border-color: #166534; background: #166534; color: #fff; }
        @media print { .preview-actions { display: none; } }
    </style>
</head>
<body>
<div class="preview-actions">
    <a href="{{ route('intakes.show', $intake) }}">Back to Intake</a>
    <button type="button" onclick="window.print()">Print</button>
</div>
<div class="sheet">
    <table class="letterhead">
        <tr>
            <td class="letterhead-mark">
                <div class="letterhead-marks">
                    <img src="{{ asset('images/intake-tagudin-seal-white.png') }}" class="logo tagudin-mark" alt="Bayan ng Tagudin seal">
                    <img src="{{ asset('images/intake-asap-seal-white.png') }}" class="logo asap-mark" alt="ASAP Tagudin seal">
                </div>
            </td>
            <td class="letterhead-center">
                <div class="small">Republic of the Philippines</div>
                <div class="small">Province of Ilocos Sur</div>
                <div class="municipality">Municipality of Tagudin</div>
            </td>
            <td class="letterhead-mark">
                <img src="{{ asset('images/intake-national-seal.png') }}" class="logo national-mark" alt="DSWD logo">
            </td>
        </tr>
    </table>
    <div class="letterhead-office">OFFICE OF THE MUNICIPAL SOCIAL WELFARE AND DEVELOPMENT OFFICER</div>

    <table class="form" style="margin-top:1px;">
        <tr>
            <th colspan="3" class="section-title">GENERAL INTAKE SHEET</th>
        </tr>
        <tr>
            <td style="width:35%;">
                {!! $checked(($intake?->program ?? 'AICS') === 'AICS') !!} AICS<br>
                {!! $checked(($intake?->program ?? '') === 'ESA') !!} ESA<br>
                {!! $checked(($intake?->program ?? '') === 'Others') !!} OTHERS: __________
            </td>
            <td style="width:25%;">
                {!! $checked(($intake?->client_type ?? 'new') === 'new') !!} NEW<br>
                {!! $checked(($intake?->client_type ?? '') === 'returning') !!} RETURNING
            </td>
            <td style="width:40%;">
                DATE: <span class="field field-medium">{{ $date }}</span><br><br>
                CASE NUMBER: <span class="field field-medium">{{ $intake->intake_number }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="3" class="tight">
                <b>CLIENT'S NAME:</b> <span class="field field-wide">{{ $client }}</span><br>
                Age: <span class="field field-tiny">{{ $intake?->age }}</span>&nbsp;
                Sex: <span class="field field-small">{{ $intake?->sex }}</span>&nbsp;
                Civil Status: <span class="field" style="min-width:60px;">{{ $intake?->civil_status }}</span>&nbsp;
                Occupation: <span class="field" style="min-width:110px;">{{ $intake?->occupation }}</span>&nbsp;
                Monthly Income: Php <span class="field field-small">{{ number_format((float)($intake?->monthly_income ?? 0), 2) }}</span><br>
                Address: Brgy. <span class="field" style="min-width:180px;">{{ $intake?->barangay?->name }}</span>, Tagudin, Ilocos Sur
            </td>
        </tr>
        <tr>
            <td colspan="3" class="tight">
                <b>BENEFICIARY'S NAME:</b> {!! $checked($person === $client) !!} SAME AS ABOVE &nbsp;&nbsp;
                <span class="field field-wide">{{ $person !== $client ? $person : '' }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2" class="tight">
                Purpose of Assistance: <span class="field" style="min-width:220px;">{{ $intake?->purpose_of_assistance }}@if($intake?->assistance_specification) — {{ $intake->assistance_specification }}@endif</span>
            </td>
            <td class="tight">
                Amount: Php <span class="field field-small">{{ number_format((float) $amount, 2) }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="3" class="tight">
                Diagnosis/Cause of Death (if burial): <span class="field" style="min-width:400px;">{{ $intake?->diagnosis_or_cause_of_death }}</span>
            </td>
        </tr>
        <tr>
            <th colspan="3" class="section-title" style="padding:2px 4px;font-size:10.5px;">FAMILY COMPOSITION</th>
        </tr>
        <tr>
            <td colspan="3" style="padding:0;">
                <table class="family-grid">
                    <tr>
                        <th style="width:22%;">NAME</th>
                        <th style="width:12%;">RELATION TO CLIENT</th>
                        <th style="width:6%;">AGE</th>
                        <th style="width:6%;">SEX</th>
                        <th style="width:10%;">CIVIL STATUS</th>
                        <th style="width:16%;">EDUCATIONAL ATTAINMENT</th>
                        <th style="width:10%;">OCCUPATION</th>
                        <th style="width:8%;">INCOME</th>
                        <th style="width:10%;">REMARKS</th>
                    </tr>
                    @foreach($members as $member)
                        <tr class="family-row">
                            <td>{{ $member->name }}</td>
                            <td>{{ $member->relationship_to_client }}</td>
                            <td style="text-align:center;">{{ $member->age }}</td>
                            <td style="text-align:center;">{{ $member->sex }}</td>
                            <td>{{ $member->civil_status }}</td>
                            <td>{{ $member->educational_attainment }}</td>
                            <td>{{ $member->occupation }}</td>
                            <td>{{ $member->income }}</td>
                            <td>{{ $member->remarks }}</td>
                        </tr>
                    @endforeach
                    @for($i = 0; $i < max(0, 6 - $members->count()); $i++)
                        <tr class="family-row">
                            <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                        </tr>
                    @endfor
                </table>
            </td>
        </tr>
        <tr>
            <td style="width:33%; vertical-align:top;">
                <b>CLIENT'S SECTOR</b><br>
                {!! $checked(in_array('Solo Parent', $clientSectors)) !!} Solo Parent &nbsp;&nbsp; {!! $checked(in_array('PWDs', $clientSectors)) !!} PWDs<br>
                {!! $checked(in_array('WEDC', $clientSectors)) !!} WEDC &nbsp;&nbsp; {!! $checked(in_array('No Regular Income', $clientSectors)) !!} No Regular Income<br>
                {!! $checked(in_array('Senior Citizens', $clientSectors)) !!} Senior Citizens<br>
                {!! $checked(in_array('4Ps Beneficiary', $clientSectors)) !!} 4Ps Beneficiary<br>
                {!! $checked(in_array('Others', $clientSectors)) !!} Others: __________
            </td>
            <td style="width:34%; vertical-align:top;">
                <b>INCOME AND FINANCIAL RESOURCES</b><br>
                {!! $checked(in_array('Employed', $incomeResources)) !!} Employed: <span class="field field-tiny">{{ $intake?->employed_members_count ?? '' }}</span><br>
                <span class="mini">(Indicate number of members working)</span><br>
                {!! $checked(in_array('Seasonal', $incomeResources)) !!} Seasonal: <span class="field field-tiny">{{ $intake?->seasonal_members_count ?? '' }}</span><br>
                <span class="mini">(Indicate number of members working)</span><br>
                {!! $checked(filled($intake?->combined_family_income)) !!} Combined family income<br>
                Php <span class="field field-small">{{ number_format((float)($intake?->combined_family_income ?? 0), 2) }}</span>/month
            </td>
            <td style="width:33%; vertical-align:top;">
                <b>BUDGET AND EXPENSES</b><br>
                Monthly expenses of the family<br>
                Php <span class="field field-small">{{ number_format((float)($intake?->monthly_expenses ?? 0), 2) }}</span><br>
                <span class="mini">(Utility bills, Maintenance, Medication, Mortgage/Rent, Debt and others)</span>
            </td>
        </tr>
        <tr>
            <td colspan="3" class="tight">
                <b>SUPPORTING DOCUMENTS SUBMITTED</b>
                <table style="width:100%;border:none;margin-top:2px;font-size:8px;table-layout:fixed;">
                    <tr>
                        <td style="border:none;padding:1px;width:25%;">
                            {!! $checked(in_array('Valid ID', $submittedDocs)) !!} Valid ID<br>
                            {!! $checked(in_array('Certificate of Indigency', $submittedDocs)) !!} Certificate of Indigency<br>
                            {!! $checked(in_array('Medical Certificate', $submittedDocs)) !!} Medical Certificate<br>
                            {!! $checked(in_array('Medical Abstract', $submittedDocs)) !!} Medical Abstract<br>
                            {!! $checked(in_array('Hospital Billing Statement', $submittedDocs)) !!} Hospital Billing Statement
                        </td>
                        <td style="border:none;padding:1px;width:25%;">
                            {!! $checked(in_array('Prescription', $submittedDocs)) !!} Prescription<br>
                            {!! $checked(in_array('Imaging Request', $submittedDocs)) !!} Imaging Request<br>
                            {!! $checked(in_array('Laboratory Request', $submittedDocs)) !!} Laboratory Request<br>
                            {!! $checked(in_array('Funeral Contract', $submittedDocs)) !!} Funeral Contract<br>
                            {!! $checked(in_array('Death Certificate', $submittedDocs)) !!} Death Certificate
                        </td>
                        <td style="border:none;padding:1px;width:25%;">
                            {!! $checked(in_array('Enrollment Fee', $submittedDocs)) !!} Enrollment Fee<br>
                            {!! $checked(in_array('Certificate of Enrollment', $submittedDocs)) !!} Certificate of Enrollment<br>
                            {!! $checked(in_array('Fare Ticket', $submittedDocs)) !!} Fare Ticket<br>
                            {!! $checked(in_array('Letter of Request', $submittedDocs)) !!} Letter of Request<br>
                            {!! $checked(in_array('Barangay Certification', $submittedDocs)) !!} Barangay Certification
                        </td>
                        <td style="border:none;padding:1px;width:25%;">
                            {!! $checked(in_array('Certificate of Residency', $submittedDocs)) !!} Certificate of Residency<br>
                            {!! $checked(in_array('Others', $submittedDocs)) !!} Others: __________
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="3" class="tight">
                <b>SOCIAL WORKER'S ASSESSMENT</b>
                <div class="assessment">{{ $intake?->social_worker_assessment ?: "Client's family belongs to the less fortunate member of the community having no fix source of income. They have limited financial resources that could hardly enough to suffice their everyday needs. Family is emotionally and financially depressed brought about their present situation. Relatives couldn't help them financially as they are also experiencing financial problem, thus client came here for augmentation. Therefore, client is hereby recommended for financial assistance to help family lessen with their present crisis situation." }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="3" class="tight">
                <b>CERTIFICATION</b><br>
                <span class="subtle">I certify that the information I have provided is true, complete and correct to the best of my knowledge. I understand that any false or misleading information may affect my eligibility for financial assistance.</span><br><br>
                <table style="width:100%;border:none;table-layout:fixed;">
                    <tr>
                        <td style="width:50%;border:none;vertical-align:top;">
                            Applicant's Signature: ____________________________<br>
                            Printed Name: <b>{{ $client }}</b><br>
                            Contact Number: {{ $intake?->contact ?? '-' }}
                        </td>
                        <td style="width:50%;border:none;vertical-align:top;">
                            Interviewer Signature: ____________________________<br>
                            Printed Name: <b>{{ $intake?->interviewer_name ?? auth()->user()?->name ?? 'LAARNI L. ACOSTA, RSW' }}</b><br>
                            Position: {{ $intake?->interviewer_position ?? 'Social Officer' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
