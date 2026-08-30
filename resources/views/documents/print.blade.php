@php
    $client = $intake?->client_name ?? $caseRecord->client_name;
    $person = $beneficiary?->name ?? $intake?->beneficiary_name ?? $client;
    $date = $fields['date'] ?? ($intake?->intake_date?->format('F d, Y') ?? now()->format('F d, Y'));
    $amount = $beneficiary?->amount ?? $intake?->requested_amount ?? 0;
    $assistance = $beneficiary?->assistanceType?->name ?? $intake?->purpose_of_assistance ?? '';
    $checked = fn ($value) => $value ? '☑' : '☐';
    $clientSectors = (array) ($intake?->client_sectors ?? []);
    $incomeResources = (array) ($intake?->income_resources ?? []);
    $submittedDocs = (array) ($intake?->supporting_documents ?? []);
    $programCategories = (array) ($fields['program_category'] ?? []);
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
@page{margin:8mm 10mm 8mm 10mm}
body{font-family:DejaVu Sans,sans-serif;font-size:9px;color:#111;line-height:1.3}
.header-table{width:100%;border-collapse:collapse;margin-bottom:6px}
.header-table td{border:none;padding:0;vertical-align:middle}
.header-left{width:22%;text-align:left}
.header-center{width:56%;text-align:center;line-height:1.2}
.header-right{width:22%;text-align:right}
.header-seal{height:46px;vertical-align:middle}
.form{width:100%;border-collapse:collapse}
.form td,.form th{border:1px solid #111;padding:3px;vertical-align:top}
.center{text-align:center}
.title{font-size:12px;font-weight:bold}
.sub{font-size:10px;font-weight:bold}
.line{border-bottom:1px solid #111;display:inline-block;min-width:100px;padding:0 2px}
.write{min-height:45px;white-space:pre-line;line-height:1.4}
.row td{height:18px}
.signature{border-top:1px solid #111;text-align:center;width:220px;margin-top:25px;padding-top:2px}
.app-container{font-size:9.5px;line-height:1.9}
.certificate{font-family:DejaVu Serif,serif;font-size:11.5px;line-height:1.85;padding:10px 15px}
.certificate .signature{margin-top:40px}
</style>
</head>
<body>

{{-- Standard Official Letterhead --}}
<table class="header-table">
    <tr>
        <td class="header-left">
            @if(extension_loaded('gd') && file_exists(public_path('images/tagudin-seal.png')))
                <img src="{{ public_path('images/tagudin-seal.png') }}" class="header-seal" style="margin-right:2px;" alt="Seal">
            @endif
            @if(extension_loaded('gd') && file_exists(public_path('images/asap-seal.png')))
                <img src="{{ public_path('images/asap-seal.png') }}" class="header-seal" alt="ASAP">
            @endif
        </td>
        <td class="header-center">
            <div style="font-size:9px;">Republic of the Philippines</div>
            <div style="font-size:9px;">Province of Ilocos Sur</div>
            <div style="font-size:10.5px;font-weight:bold;">MUNICIPALITY OF TAGUDIN</div>
            <div style="font-size:9.5px;font-weight:bold;margin-top:1px;">OFFICE OF THE MUNICIPAL SOCIAL WELFARE AND DEVELOPMENT OFFICER</div>
            @if($document->template === 'application_form')
                <div style="font-size:9.5px;font-weight:bold;">BUREAU OF EMERGENCY ASSISTANCE</div>
            @endif
        </td>
        <td class="header-right">
            @if(extension_loaded('gd') && file_exists(public_path('images/national-seal.png')))
                <img src="{{ public_path('images/national-seal.png') }}" class="header-seal" alt="National Seal">
            @endif
        </td>
    </tr>
</table>

@if($document->template === 'general_intake_sheet')
<table class="form">
    <tr>
        <th colspan="3" class="center title" style="padding:4px;">GENERAL INTAKE SHEET</th>
    </tr>
    <tr>
        <td width="35%">
            {{ $checked(($intake?->program ?? 'AICS') === 'AICS') }} AICS<br>
            {{ $checked(($intake?->program ?? '') === 'ESA') }} ESA<br>
            {{ $checked(($intake?->program ?? '') === 'Others') }} OTHERS: __________
        </td>
        <td width="25%">
            {{ $checked(($intake?->client_type ?? 'new') === 'new') }} NEW<br>
            {{ $checked(($intake?->client_type ?? '') === 'returning') }} RETURNING
        </td>
        <td width="40%">
            DATE: <span class="line">{{ $date }}</span><br><br>
            CASE NUMBER: <span class="line">{{ $caseRecord->case_number }}</span>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <b>CLIENT'S NAME:</b> <span class="line" style="min-width:320px;">{{ $client }}</span><br>
            Age: <span class="line" style="min-width:35px;">{{ $intake?->age }}</span> &nbsp;
            Sex: <span class="line" style="min-width:45px;">{{ $intake?->sex }}</span> &nbsp;
            Civil Status: <span class="line" style="min-width:60px;">{{ $intake?->civil_status }}</span> &nbsp;
            Occupation: <span class="line" style="min-width:110px;">{{ $intake?->occupation }}</span> &nbsp;
            Monthly Income: Php <span class="line" style="min-width:65px;">{{ number_format((float)($intake?->monthly_income ?? 0),2) }}</span><br>
            Address: Brgy. <span class="line" style="min-width:180px;">{{ $intake?->barangay?->name ?? $caseRecord->barangay?->name }}</span>, Tagudin, Ilocos Sur
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <b>BENEFICIARY'S NAME:</b> &nbsp; {{ $checked($person === $client) }} SAME AS ABOVE &nbsp;&nbsp;
            <span class="line" style="min-width:280px;">{{ $person !== $client ? $person : '' }}</span>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            Purpose of Assistance: <span class="line" style="min-width:220px;">{{ $intake?->purpose_of_assistance }}</span>
        </td>
        <td>
            Amount: Php <span class="line" style="min-width:90px;">{{ number_format((float)$amount,2) }}</span>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            Diagnosis/Cause of Death (if burial): <span class="line" style="min-width:400px;">{{ $intake?->diagnosis_or_cause_of_death }}</span>
        </td>
    </tr>
    <tr>
        <th colspan="3" class="title" style="padding:2px;font-size:10.5px;">FAMILY COMPOSITION</th>
    </tr>
    <tr>
        <td colspan="3" style="padding:0;">
            <table class="form" style="border:none;margin:-1px;">
                <tr style="background:#f4f4f4;">
                    <th style="font-size:8px;width:22%;">NAME</th>
                    <th style="font-size:8px;width:12%;">RELATION TO CLIENT</th>
                    <th style="font-size:8px;width:6%;">AGE</th>
                    <th style="font-size:8px;width:6%;">SEX</th>
                    <th style="font-size:8px;width:10%;">CIVIL STATUS</th>
                    <th style="font-size:8px;width:16%;">EDUCATIONAL ATTAINMENT</th>
                    <th style="font-size:8px;width:10%;">OCCUPATION</th>
                    <th style="font-size:8px;width:8%;">INCOME</th>
                    <th style="font-size:8px;width:10%;">REMARKS</th>
                </tr>
                @php
                    $members = $caseRecord->householdMembers->take(6);
                    $emptyCount = max(0, 6 - $members->count());
                @endphp
                @foreach($members as $m)
                    <tr class="row">
                        <td>{{ $m->name }}</td>
                        <td>{{ $m->relationship_to_client }}</td>
                        <td class="center">{{ $m->age }}</td>
                        <td class="center">{{ $m->sex }}</td>
                        <td>{{ $m->civil_status }}</td>
                        <td>{{ $m->educational_attainment }}</td>
                        <td>{{ $m->occupation }}</td>
                        <td>{{ $m->income }}</td>
                        <td>{{ $m->remarks }}</td>
                    </tr>
                @endforeach
                @for($i=0; $i<$emptyCount; $i++)
                    <tr class="row">
                        <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                    </tr>
                @endfor
            </table>
        </td>
    </tr>
    <tr>
        <td width="33%">
            <b>CLIENT'S SECTOR</b><br>
            {{ $checked(in_array('Solo Parent', $clientSectors)) }} Solo Parent &nbsp;&nbsp; {{ $checked(in_array('PWDs', $clientSectors)) }} PWDs<br>
            {{ $checked(in_array('WEDC', $clientSectors)) }} WEDC &nbsp;&nbsp; {{ $checked(in_array('No Regular Income', $clientSectors)) }} No Regular Income<br>
            {{ $checked(in_array('Senior Citizens', $clientSectors)) }} Senior Citizens<br>
            {{ $checked(in_array('4Ps Beneficiary', $clientSectors)) }} 4Ps Beneficiary<br>
            {{ $checked(in_array('Others', $clientSectors)) }} Others: __________
        </td>
        <td width="34%">
            <b>INCOME AND FINANCIAL RESOURCES</b><br>
            {{ $checked(in_array('Employed', $incomeResources)) }} Employed: <span class="line" style="min-width:30px;">{{ $intake?->employed_members_count ?? '' }}</span><br>
            <span style="font-size:7.5px;color:#444;">(Indicate number of members working)</span><br>
            {{ $checked(in_array('Seasonal', $incomeResources)) }} Seasonal: <span class="line" style="min-width:30px;">{{ $intake?->seasonal_members_count ?? '' }}</span><br>
            <span style="font-size:7.5px;color:#444;">(Indicate number of members working)</span><br>
            {{ $checked(filled($intake?->combined_family_income)) }} Combined family income<br>
            Php <span class="line" style="min-width:70px;">{{ number_format((float)($intake?->combined_family_income ?? 0),2) }}</span>/month
        </td>
        <td width="33%">
            <b>BUDGET AND EXPENSES</b><br>
            Monthly expenses of the family<br>
            Php <span class="line" style="min-width:80px;">{{ number_format((float)($intake?->monthly_expenses ?? 0),2) }}</span><br>
            <span style="font-size:7.5px;color:#555;">(Utility bills, Maintenance, Medication, Mortgage/Rent, Debt and others)</span>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <b>SUPPORTING DOCUMENTS SUBMITTED</b>
            <table style="width:100%;border:none;margin-top:2px;font-size:8px;">
                <tr>
                    <td style="border:none;padding:1px;width:25%;">
                        {{ $checked(in_array('Valid ID', $submittedDocs)) }} Valid ID<br>
                        {{ $checked(in_array('Certificate of Indigency', $submittedDocs)) }} Certificate of Indigency<br>
                        {{ $checked(in_array('Medical Certificate', $submittedDocs)) }} Medical Certificate<br>
                        {{ $checked(in_array('Medical Abstract', $submittedDocs)) }} Medical Abstract<br>
                        {{ $checked(in_array('Hospital Billing Statement', $submittedDocs)) }} Hospital Billing Statement
                    </td>
                    <td style="border:none;padding:1px;width:25%;">
                        {{ $checked(in_array('Prescription', $submittedDocs)) }} Prescription<br>
                        {{ $checked(in_array('Imaging Request', $submittedDocs)) }} Imaging Request<br>
                        {{ $checked(in_array('Laboratory Request', $submittedDocs)) }} Laboratory Request<br>
                        {{ $checked(in_array('Funeral Contract', $submittedDocs)) }} Funeral Contract<br>
                        {{ $checked(in_array('Death Certificate', $submittedDocs)) }} Death Certificate
                    </td>
                    <td style="border:none;padding:1px;width:25%;">
                        {{ $checked(in_array('Enrollment Fee', $submittedDocs)) }} Enrollment Fee<br>
                        {{ $checked(in_array('Certificate of Enrollment', $submittedDocs)) }} Certificate of Enrollment<br>
                        {{ $checked(in_array('Fare Ticket', $submittedDocs)) }} Fare Ticket<br>
                        {{ $checked(in_array('Letter of Request', $submittedDocs)) }} Letter of Request<br>
                        {{ $checked(in_array('Barangay Certification', $submittedDocs)) }} Barangay Certification
                    </td>
                    <td style="border:none;padding:1px;width:25%;">
                        {{ $checked(in_array('Certificate of Residency', $submittedDocs)) }} Certificate of Residency<br>
                        {{ $checked(in_array('Others', $submittedDocs)) }} Others: __________
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <b>SOCIAL WORKER'S ASSESSMENT</b>
            <div class="write">{{ $intake?->social_worker_assessment ?: "Client's family belongs to the less fortunate member of the community having no fix source of income. They have limited financial resources that could hardly enough to suffice their everyday needs. Family is emotionally and financially depressed brought about their present situation. Relatives couldn't help them financially as they are also experiencing financial problem, thus client came here for augmentation. Therefore, client is hereby recommended for financial assistance to help family lessen with their present crisis situation." }}</div>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <b>CERTIFICATION</b><br>
            <span style="font-size:8px;">I certify that the information I have provided is true, complete and correct to the best of my knowledge. I understand that any false or misleading information may affect my eligibility for financial assistance.</span><br><br>
            <table style="width:100%;border:none;margin-top:5px;">
                <tr>
                    <td style="width:50%;border:none;">
                        Applicant's Signature: ____________________________<br>
                        Printed Name: <span style="font-weight:bold;">{{ $client }}</span><br>
                        Contact Number: {{ $intake?->contact ?? '—' }}
                    </td>
                    <td style="width:50%;border:none;">
                        Interviewer Signature: ____________________________<br>
                        Printed Name: <span style="font-weight:bold;">{{ $intake?->interviewer_name ?? auth()->user()?->name ?? 'LAARNI L. ACOSTA, RSW' }}</span><br>
                        Position: {{ $intake?->interviewer_position ?? 'Social Officer' }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

@elseif($document->template === 'case_record')
<div class="center title" style="margin-top:10px;">CASE RECORD</div>
<table class="form" style="margin-top:15px">
    <tr>
        <td width="60%">
            <b>NAME:</b> <span class="line" style="min-width:240px;">{{ $client }}</span><br><br>
            <b>BARANGAY:</b> <span class="line" style="min-width:200px;">{{ $caseRecord->barangay?->name }}</span>
        </td>
        <td>
            <b>DATE OF ENTRY:</b> <span class="line">{{ $caseRecord->date_of_entry?->format('F d, Y') }}</span><br><br>
            <b>CASE NO.</b> <span class="line">{{ $caseRecord->case_number }}</span>
        </td>
    </tr>
    <tr>
        <th style="width:30%;">NAME</th>
        <th>RELATION TO CLIENT · BIRTHDAY/AGE · SEX · CIVIL STATUS · RELIGION · EDUCATIONAL ATTAINMENT · OCCUPATION · INCOME · REMARKS</th>
    </tr>
    @foreach($caseRecord->householdMembers->take(12) as $member)
        <tr class="row">
            <td>{{ $member->name }}</td>
            <td>{{ $member->relationship_to_client }} · {{ $member->birth_date?->format('m/d/Y') ?? $member->age }} · {{ $member->sex }} · {{ $member->civil_status }} · {{ $member->religion }} · {{ $member->educational_attainment }} · {{ $member->occupation }} · {{ $member->income }} · {{ $member->remarks }}</td>
        </tr>
    @endforeach
</table>
<div class="app-container" style="margin-top:12px">
    <b>PROBLEM PRESENTED:</b> <span class="line" style="min-width:350px;">{{ $caseRecord->problem_presented }}</span><br>
    Pantawid Pamilya Member: <span class="line">{{ $caseRecord->is_government_program_member ? implode(', ', $caseRecord->government_programs ?? []) : 'No' }}</span><br>
    Housing Structure: <span class="line">{{ $caseRecord->house_structure }}</span> &nbsp; Water Source: <span class="line">{{ $caseRecord->water_source }}</span> &nbsp; Electricity: <span class="line">{{ $caseRecord->electricity }}</span>
</div>
<table style="width:100%;border:none;margin-top:20px;">
    <tr>
        <td style="width:50%;border:none;"><div class="signature" style="margin:20px auto 0;">SIGNATURE OF CLIENT</div></td>
        <td style="width:50%;border:none;"><div class="signature" style="margin:20px auto 0;">INTERVIEWER</div></td>
    </tr>
</table>

@elseif($document->template === 'application_form')
<div class="center title" style="margin:6px 0 10px;">APPLICATION FORM</div>
<div class="app-container">
    <table style="width:100%;border:none;">
        <tr>
            <td style="width:50%;border:none;">
                {{ $checked(($fields['resettled'] ?? '') === 'resettled' || ($fields['resettled'] ?? '') === 'Resettled' || ($fields['resettled'] ?? false) === true) }} <b>Resettled</b>
            </td>
            <td style="width:50%;border:none;text-align:right;">
                {{ $checked(($fields['resettled'] ?? '') === 'not_resettled' || ($fields['resettled'] ?? '') === 'Not Resettled' || ($fields['resettled'] ?? false) === false && isset($fields['resettled'])) }} <b>Not Resettled</b>
            </td>
        </tr>
    </table>

    <div style="margin-top:4px;">
        <b>Disaster Victim:</b> &nbsp;
        ND {{ $checked(($fields['disaster_type'] ?? '') === 'ND') }} <span class="line" style="min-width:180px;"></span> &nbsp;&nbsp;&nbsp;&nbsp;
        MD {{ $checked(($fields['disaster_type'] ?? '') === 'MD') }} <span class="line" style="min-width:180px;"></span>
    </div>

    <div style="margin-top:4px;">
        <b>Date of Occurrence:</b> <span class="line" style="min-width:200px;">{{ $fields['date_of_occurrence'] ?? '' }}</span>
    </div>

    <div style="margin-top:6px;">
        <b>Type of Assistance:</b>
        <table style="width:100%;border:none;margin-left:15px;margin-top:2px;">
            <tr>
                <td style="width:50%;border:none;padding:1px;">{{ $checked(in_array('Cultural Minorities', $programCategories)) }} Cultural Minorities</td>
                <td style="width:50%;border:none;padding:1px;">{{ $checked(in_array('Returnee', $programCategories)) }} Returnee</td>
            </tr>
            <tr>
                <td style="width:50%;border:none;padding:1px;">{{ $checked(in_array('Refugees', $programCategories)) }} Refugees</td>
                <td style="width:50%;border:none;padding:1px;">{{ $checked(in_array('Squatter', $programCategories)) }} Squatter</td>
            </tr>
            <tr>
                <td style="width:50%;border:none;padding:1px;">{{ $checked(in_array('Evacuees', $programCategories)) }} Evacuees</td>
                <td style="width:50%;border:none;padding:1px;">{{ $checked(in_array('ESA', $programCategories)) }} ESA</td>
            </tr>
            <tr>
                <td style="width:50%;border:none;padding:1px;">{{ $checked(in_array('AICS', $programCategories) || empty($programCategories)) }} AICS</td>
                <td style="width:50%;border:none;padding:1px;">{{ $checked(in_array('Others', $programCategories)) }} Others (Specify)</td>
            </tr>
        </table>
    </div>

    <div style="margin-top:6px;">
        <b>FAMILY HEAD:</b> <span class="line" style="min-width:260px;">{{ $fields['family_head_name'] ?? $client }}</span><br>
        <b>Address:</b> <span class="line" style="min-width:240px;">{{ $caseRecord->barangay?->name ?? $intake?->barangay?->name }}</span>, Tagudin, Ilocos Sur
    </div>

    <table class="form" style="margin-top:6px;">
        <tr style="background:#f4f4f4;">
            <th style="width:50%;">NAME</th>
            <th style="width:35%;">RELATION TO CLIENT</th>
            <th style="width:15%;">AGE</th>
        </tr>
        @php
            $householdMembers = $caseRecord->householdMembers->take(6);
            $emptyHouseholdCount = max(0, 6 - $householdMembers->count());
        @endphp
        @foreach($householdMembers as $hm)
            <tr class="row">
                <td>{{ $hm->name }}</td>
                <td>{{ $hm->relationship_to_client }}</td>
                <td class="center">{{ $hm->age }}</td>
            </tr>
        @endforeach
        @for($i=0; $i<$emptyHouseholdCount; $i++)
            <tr class="row">
                <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
            </tr>
        @endfor
    </table>

    <table style="width:100%;border:none;margin-top:8px;">
        <tr>
            <td style="width:50%;border:none;vertical-align:top;">
                <b>Purpose</b><br>
                <div style="margin-left:10px;line-height:1.6;">
                    {{ $checked($assistance === 'Medical Assistance') }} Medical Assistance<br>
                    {{ $checked($assistance === 'Food Assistance' || $assistance === 'Nutritional Foods') }} Food Assistance<br>
                    {{ $checked($assistance === 'Livelihood Assistance') }} Livelihood Assistance<br>
                    {{ $checked($assistance === 'Educational Assistance') }} Educational Assistance<br>
                    {{ $checked($assistance === 'Transportation Assistance') }} Transportation Assistance<br>
                    {{ $checked($assistance === 'Construction Materials') }} Construction Materials<br>
                    {{ $checked($assistance === 'Burial Service' || $assistance === 'Burial Assistance') }} Burial Service
                </div>
            </td>
            <td style="width:50%;border:none;vertical-align:top;">
                <b>Estimated Damaged:</b> <span class="line" style="min-width:140px;">{{ $fields['estimated_damage'] ?? '' }}</span><br>
                <b>Others:</b> <span class="line" style="min-width:180px;">{{ $fields['others'] ?? '' }}</span><br><br>
                <b>CASUALTIES:</b><br>
                <b>NAME:</b> <span class="line" style="min-width:200px;">{{ $fields['casualties'] ?? '' }}</span>
            </td>
        </tr>
    </table>

    <div style="margin-top:20px;text-align:center;">
        <div class="signature" style="margin:0 auto 20px;width:260px;">
            Signature or Thumb mark of client
        </div>
    </div>

    <div style="margin-top:15px;text-align:center;">
        <div class="signature" style="margin:0 auto;width:280px;">
            <b>{{ $settings['social_worker_name'] ?? 'LAARNI L. ACOSTA, RSW' }}</b><br>
            {{ $settings['social_worker_title'] ?? 'Municipal Social Welfare and Development Officer' }}
        </div>
    </div>

    <div style="font-size:8px;color:#555;margin-top:10px;">
        Date Registered: {{ $date }} &nbsp;&nbsp;&nbsp;&nbsp; <i>To be attached to the voucher</i>
    </div>
</div>

@else
{{-- Certificate of Eligibility (BA Form 206, Series of 1986) --}}
<div style="position:relative;margin-top:5px;">
    <div style="font-size:8.5px;line-height:1.3;">
        <b>BA FORM 206</b><br>
        Series of 1986<br>
        <b>CN:</b> {{ $caseRecord->case_number }}
    </div>
</div>

<div class="certificate">
    <div class="center title" style="font-size:14px;letter-spacing:1px;margin:15px 0 20px;">
        CERTIFICATE OF ELIGIBILITY
    </div>

    <div style="text-align:justify;margin-bottom:14px;">
        This is to certify that <span class="line" style="min-width:320px;font-weight:bold;">{{ $person }}</span>
        Barangay <span class="line" style="min-width:200px;">{{ $caseRecord->barangay?->name ?? $intake?->barangay?->name }}</span>, Tagudin, Ilocos Sur has been found eligible
        for <span class="line" style="min-width:180px;font-weight:bold;">{{ $assistance ?: ($intake?->program ?? 'AICS') }}</span> under the Bureau of Emergency Assistance following the
        interview and case study.
    </div>

    <div style="text-align:justify;margin-bottom:14px;">
        Records of the case dated <span class="line" style="min-width:200px;">{{ $caseRecord->date_of_entry?->format('F d, Y') ?? $date }}</span> are securely stored in the
        confidential file of Tagudin Unit Office.
    </div>

    <div style="text-align:justify;margin-bottom:14px;">
        Client is recommended for assistance in the amount of <span class="line" style="min-width:180px;font-weight:bold;">Php {{ number_format((float)$amount, 2) }}</span>
        and/or for <span class="line" style="min-width:240px;">{{ $assistance }}</span>
    </div>

    <div style="text-align:justify;margin-bottom:20px;">
        Records and Case Study reviewed: <span class="line" style="min-width:250px;">{{ $fields['case_narrative'] ?? '' }}</span>
    </div>

    {{-- Reviewer Signature Line --}}
    <div style="margin-top:30px;">
        <div class="signature" style="text-align:left;width:280px;margin-left:0;">
            <div style="text-align:center;">
                <b>{{ $settings['social_worker_name'] ?? 'LAARNI L. ACOSTA, RSW' }}</b><br>
                <span style="font-size:9.5px;">{{ $settings['social_worker_title'] ?? 'Municipal Social Welfare and Development Officer' }}</span>
            </div>
        </div>
    </div>

    {{-- Mayor Approval Signature Line --}}
    <div style="margin-top:25px;margin-left:50%;text-align:center;">
        <div style="text-align:left;font-size:10px;font-weight:bold;margin-bottom:20px;">APPROVED:</div>
        <div class="signature" style="margin:0 auto;width:260px;">
            <b>{{ $settings['mayor_name'] ?? 'EVANGELINE I. VERZOSA' }}</b><br>
            <span style="font-size:9.5px;">{{ $settings['mayor_title'] ?? 'Municipal Mayor' }}</span>
        </div>
    </div>
</div>
@endif

</body>
</html>
