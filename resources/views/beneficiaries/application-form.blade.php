@php
    $caseRecord = $beneficiary->caseRecord;
    $intake = $caseRecord?->intake;
    $client = $intake?->client_name ?? $caseRecord?->client_name ?? $beneficiary->name;
    $person = $intake?->beneficiary_name ?: $client;
    $barangay = $beneficiary->barangay?->name ?? $intake?->barangay?->name ?? $caseRecord?->barangay?->name ?? '';
    $assistance = $beneficiary->assistanceType?->name ?? $intake?->purpose_of_assistance ?? '';
    $amount = $beneficiary->amount ?? $intake?->requested_amount ?? 0;
    $date = $beneficiary->date_of_assistance?->format('F d, Y')
        ?? $intake?->intake_date?->format('F d, Y')
        ?? now()->format('F d, Y');
    $settings = \App\Models\OfficialSetting::values();
    $socialWorker = $settings['social_worker_name'] ?? 'LAARNI L. ACOSTA, RSW';
    $mayor = $settings['mayor_name'] ?? 'EVANGELINE I. VERZOSA';
    $members = $caseRecord?->householdMembers?->take(10) ?? collect();
    $final = $beneficiary->final_aics_payload ?? [];
    $checked = fn (bool $value): string => $value ? '&#9745;' : '&#9744;';
    $isAssistance = fn (string $needle): bool => str_contains(strtolower($assistance), strtolower($needle));
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 12mm 14mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .page { min-height: 273mm; page-break-after: always; position: relative; }
        .page:last-child { page-break-after: auto; }
        .center { text-align: center; }
        .official { line-height: 1.35; }
        .official .republic { font-size: 12px; }
        .official .municipality { font-size: 15px; font-weight: bold; }
        .official .office { font-size: 11px; font-weight: bold; }
        .line { display: inline-block; min-width: 100px; border-bottom: 1px solid #111; padding: 0 3px 1px; }
        .certificate { font-family: DejaVu Serif, serif; font-size: 11px; line-height: 1.8; }
        .certificate-title { font-size: 18px; font-weight: bold; margin: 28px 0 22px; }
        .certificate-text { text-align: justify; margin: 0 0 18px; }
        .signature { border-top: 1px solid #111; text-align: center; padding-top: 4px; }
        .application-title { font-size: 17px; font-weight: bold; margin: 24px 0 26px; }
        .application { font-size: 10px; line-height: 1.35; }
        .application-row { margin: 10px 0; }
        .checks { line-height: 1.8; }
        .family { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .family th, .family td { border-bottom: 1px solid #111; padding: 4px 3px; height: 20px; }
        .family th { text-align: left; font-size: 9px; }
        .purpose { width: 48%; line-height: 1.8; }
        .receipt { border: 1px solid #111; height: 121mm; font-family: DejaVu Sans, sans-serif; }
        .receipt-head { display: table; width: 100%; height: 18mm; border-bottom: 1px solid #111; }
        .receipt-head > div { display: table-cell; vertical-align: top; padding: 4px; }
        .receipt-title { background: #fff900; border-bottom: 1px solid #111; font-family: DejaVu Serif, serif; font-size: 14px; font-weight: bold; text-align: center; padding: 5px; }
        .receipt-section { background: #4dff58; border-top: 1px solid #111; border-bottom: 1px solid #111; font-family: DejaVu Serif, serif; font-size: 13px; font-weight: bold; text-align: center; padding: 4px; }
        .receipt-body { padding: 7px 12px; line-height: 1.7; }
        .receipt-line { display: inline-block; border-bottom: 1px solid #111; min-width: 115px; height: 15px; vertical-align: bottom; }
        .receipt-grid { width: 100%; border-collapse: collapse; }
        .receipt-grid td { padding: 0 3px; vertical-align: top; }
        .receipt-space { height: 18mm; border-bottom: 1px solid #111; }
        .receipt-sheet { display: table; width: 100%; table-layout: fixed; border-spacing: 8mm 0; margin-left: -8mm; }
        .receipt-cell { display: table-cell; width: 50%; }
        .voucher-note { position: absolute; bottom: 8mm; left: 0; right: 0; text-align: center; font-size: 9px; }
    </style>
</head>
<body>
    <section class="page certificate">
        <div class="official center">
            <div class="republic">Republic of the Philippines</div>
            <div class="municipality">MUNICIPALITY OF TAGUDIN</div>
            <div>Province of Ilocos Sur</div>
            <div class="office" style="margin-top: 22px;">OFFICE OF THE<br>MUNICIPAL SOCIAL WELFARE AND DEVELOPMENT OFFICER</div>
        </div>
        <div style="margin-top: 24px; font-family: DejaVu Sans, sans-serif; font-size: 9px;">
            <b>BA FORM 206</b><br>Series of 1986<br><b>CN:</b> {{ $caseRecord?->case_number ?? $beneficiary->reference_code }}
        </div>
        <div class="center certificate-title">CERTIFICATE OF ELIGIBILITY</div>
        <p class="certificate-text">This is to certify that <span class="line" style="min-width: 270px; font-weight: bold;">{{ $person }}</span> of Barangay <span class="line" style="min-width: 130px;">{{ $barangay }}</span>, Ilocos Sur has been found eligible for <span class="line" style="min-width: 130px; font-weight: bold;">{{ $assistance ?: 'AICS' }}</span> under the Bureau of Emergency Assistance following the interview and case study.</p>
        <p class="certificate-text">Records of the case dated <span class="line" style="min-width: 150px;">{{ $caseRecord?->date_of_entry?->format('F d, Y') ?? $date }}</span> are securely stored in the confidential file of Tagudin Unit Office.</p>
        <p class="certificate-text">Client is recommended for assistance in the amount of <span class="line" style="min-width: 135px; font-weight: bold;">Php {{ number_format((float) $amount, 2) }}</span> and/or <span class="line" style="min-width: 160px;">{{ $assistance }}</span>.</p>
        <p class="certificate-text">Records and Case Study reviewed: <span class="line" style="min-width: 210px;"></span></p>
        <div style="margin-top: 35px; width: 48%;">
            <div class="signature"><b>{{ $socialWorker }}</b><br>Municipal Social Welfare and Development Officer</div>
        </div>
        <div style="margin: 35px 0 0 52%; width: 48%;">
            <b>APPROVED:</b>
            <div class="signature" style="margin-top: 25px;"><b>{{ $mayor }}</b><br>Municipal Mayor</div>
        </div>
    </section>

    <section class="page application">
        <div class="official center">
            <div class="republic">Republic of the Philippines</div>
            <div class="municipality">MUNICIPALITY OF TAGUDIN</div>
            <div>Province of Ilocos Sur</div>
            <div class="office" style="margin-top: 14px;">OFFICE OF THE MUNICIPAL SOCIAL WELFARE AND DEVELOPMENT OFFICER<br>BUREAU OF EMERGENCY ASSISTANCE</div>
        </div>
        <div class="application-title">APPLICATION FORM</div>
        <div class="application-row">
            <span class="line" style="min-width: 170px;">{{ !empty($final['resettled']) ? 'X' : '' }}</span> Resettled
            <span style="float: right;"><span class="line" style="min-width: 170px;">{{ empty($final['resettled']) ? 'X' : '' }}</span> Not Resettled</span>
        </div>
        <div class="application-row"><b>Disaster Victim:</b> ND <span class="line" style="min-width: 70px;">{{ ($final['disaster_victim'] ?? '') === 'ND' ? 'X' : '' }}</span> MD <span class="line" style="min-width: 70px;">{{ ($final['disaster_victim'] ?? '') === 'MD' ? 'X' : '' }}</span></div>
        <div class="application-row"><b>Date of Occurrence:</b> <span class="line" style="min-width: 190px;">{{ $final['date_of_occurrence'] ?? '' }}</span></div>
        <div class="application-row"><b>Type of Assistance:</b>
            <table style="width: 100%;"><tr><td class="checks">{!! $checked(false) !!} Cultural Minorities<br>{!! $checked(false) !!} Refugees<br>{!! $checked(false) !!} Evacuees<br>{!! $checked($isAssistance('AICS')) !!} AICS</td><td class="checks">{!! $checked(false) !!} Returnee<br>{!! $checked(false) !!} Squatter<br>{!! $checked(false) !!} ESA<br>{!! $checked($isAssistance('Other') || $isAssistance('Miscellaneous')) !!} Others (Specify) {{ $intake?->assistance_specification }}</td></tr></table>
        </div>
        <div class="application-row"><b>FAMILY HEAD:</b> <span class="line" style="min-width: 250px;">{{ $client }}</span><br><b>Address:</b> <span class="line" style="min-width: 250px;">{{ $barangay }}</span>, Tagudin, Ilocos Sur</div>
        <table class="family">
            <tr><th style="width: 52%;">NAME</th><th style="width: 35%;">RELATION TO CLIENT</th><th style="width: 13%;">AGE</th></tr>
            @foreach($members as $member)
                <tr><td>{{ $member->name }}</td><td>{{ $member->relationship_to_client }}</td><td>{{ $member->age }}</td></tr>
            @endforeach
            @for($i = $members->count(); $i < 10; $i++) <tr><td>&nbsp;</td><td></td><td></td></tr> @endfor
        </table>
        <table style="width: 100%; margin-top: 18px;"><tr><td class="purpose"><b>Purpose</b><br>{!! $checked($isAssistance('Medical')) !!} Medical Assistance<br>{!! $checked($isAssistance('Food')) !!} Food Assistance<br>{!! $checked($isAssistance('Livelihood')) !!} Livelihood Assistance<br>{!! $checked($isAssistance('Educational')) !!} Educational Assistance<br>{!! $checked($isAssistance('Transportation')) !!} Transportation Assistance<br>{!! $checked($isAssistance('Construction')) !!} Construction Materials<br>{!! $checked($isAssistance('Burial')) !!} Burial Service</td><td style="vertical-align: top;"><b>Estimated Damaged:</b> <span class="line" style="min-width: 120px;">{{ $final['estimated_damage'] ?? '' }}</span><br><b>Others:</b> <span class="line" style="min-width: 160px;">{{ $intake?->assistance_specification }}</span><br><br><b>CASUALTIES:</b><br><b>NAME:</b> <span class="line" style="min-width: 180px;">{{ $final['casualty_name'] ?? '' }}</span></td></tr></table>
        <div style="margin: 18px auto 0; width: 300px; text-align: center;" class="signature">Signature or Thumb mark of client</div>
        <div style="margin: 30px auto 0; width: 280px;" class="signature"><b>{{ $socialWorker }}</b></div>
    </section>

    <section class="page">
        <div class="official center" style="margin-top: 35mm;">
            <div class="republic">Republic of the Philippines</div>
            <div class="municipality">MUNICIPALITY OF TAGUDIN</div>
            <div>Province of Ilocos Sur</div>
            <div class="office" style="margin-top: 24px;">MUNICIPAL SOCIAL WELFARE AND DEVELOPMENT OFFICER</div>
        </div>
        <div style="margin-top: 90mm; text-align: center; font-size: 12px;">
            Date Registered: <span class="line" style="min-width: 150px;">{{ $date }}</span>
            <div style="margin-top: 20px;">To be attached to the voucher</div>
        </div>
    </section>

    @for($sheet = 0; $sheet < 2; $sheet++)
        <section class="page">
            <div class="receipt-sheet">
                @for($copy = 0; $copy < 2; $copy++)
                    <div class="receipt-cell">
                        <div class="receipt">
                            <div class="receipt-head"><div>Gen. Form No. 2<br>Revised January 1992</div><div style="text-align: right;">Appendix 18</div></div>
                            <div class="receipt-title">REIMBURSEMENT EXPENSE RECEIPT</div>
                            <div class="receipt-body">
                                Date: <span class="receipt-line">{{ $final['receipt_date'] ?? '' }}</span> &nbsp; No.: <span class="receipt-line">{{ $final['receipt_number'] ?? '' }}</span><br>
                                RECEIVED from <span class="receipt-line" style="min-width: 145px;">{{ $person }}</span><br>
                                <span class="receipt-line" style="min-width: 220px;">Municipal Treasurer</span> the amount<br>
                                of <span class="receipt-line" style="min-width: 180px;">{{ isset($final['receipt_amount']) ? 'Php '.number_format((float) $final['receipt_amount'], 2) : '' }}</span><br>
                                in payment for <span class="receipt-line" style="min-width: 190px;">{{ $final['receipt_purpose'] ?? $assistance }}</span>
                                <div class="receipt-space"></div>
                                <div class="center" style="font-size: 8px;">rental or transportation should show inclusive dates,<br>purpose, distance, inclusive points of travel, etc.)</div>
                            </div>
                            <div class="receipt-section">PAYEE</div>
                            <div class="receipt-body">Name/Signature: <span class="receipt-line" style="min-width: 150px;">{{ $final['payee_name'] ?? '' }}</span><br>Address: <span class="receipt-line" style="min-width: 180px;">{{ $final['payee_address'] ?? '' }}</span><br>Comm. Tax Cert. No.: <span class="receipt-line" style="min-width: 130px;">{{ $final['payee_tax_certificate'] ?? '' }}</span><br>Date of Issue: <span class="receipt-line" style="min-width: 150px;">{{ $final['payee_date_of_issue'] ?? '' }}</span><br>Place of Issue: <span class="receipt-line" style="min-width: 145px;">{{ $final['payee_place_of_issue'] ?? '' }}</span></div>
                            <div class="receipt-section">WITNESS</div>
                            <div class="receipt-body">Name/Signature: <span class="receipt-line" style="min-width: 140px;">{{ $final['witness_name'] ?? '' }}</span></div>
                        </div>
                    </div>
                @endfor
            </div>
        </section>
    @endfor
</body>
</html>
