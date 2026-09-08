@php
    $intake = $beneficiary->intake;
    $client = $intake?->client_name ?? $beneficiary->name;
    $barangay = $beneficiary->barangay?->name ?? $intake?->barangay?->name ?? '';
    $assistance = $beneficiary->assistanceType?->name ?? $intake?->purpose_of_assistance ?? '';
    $final = $payload;
    $value = fn (string $key, mixed $fallback = '') => old($key, $final[$key] ?? $fallback);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-xl font-semibold text-gray-800">Review / Edit Final AICS Form</h2><p class="text-sm text-gray-500">{{ $beneficiary->reference_code }}</p></div>
            <a target="_blank" href="{{ route('beneficiaries.application-form', $beneficiary) }}" class="rounded bg-gray-700 px-4 py-2 text-white">Print Final AICS Form</a>
        </div>
    </x-slot>

    <style>
        .aics-editor{background:#e5e7eb;padding:24px 12px}.aics-page{width:min(794px,100%);min-height:1050px;margin:0 auto 24px;padding:38px 42px;background:#fff;color:#111;font:12px Arial,sans-serif;box-shadow:0 2px 8px #0002}.aics-official{text-align:center;line-height:1.35}.aics-official .municipality{font-size:16px;font-weight:700}.aics-official .office{margin-top:20px;font-size:12px;font-weight:700}.aics-title{text-align:center;font:700 20px Georgia,serif;margin:38px 0 28px}.aics-application-title{font-size:18px;font-weight:700;margin:28px 0}.aics-line{border:0;border-bottom:1px solid #111;border-radius:0;background:transparent;padding:2px 3px;outline:0}.aics-line:focus{border-bottom:2px solid #2563eb;background:#eff6ff}.aics-wide{width:250px}.aics-medium{width:170px}.aics-small{width:110px}.aics-full{width:100%}.aics-row{margin:14px 0}.aics-table{width:100%;border-collapse:collapse}.aics-table th,.aics-table td{border:1px solid #111;padding:5px}.aics-table th{font-size:10px}.aics-receipt{border:1px solid #111;min-height:400px;padding:12px}.aics-section{background:#4dff58;border-block:1px solid #111;text-align:center;font:bold 15px Georgia,serif;padding:5px;margin:15px -12px}.aics-actions{position:sticky;top:0;z-index:5;width:min(794px,100%);margin:0 auto 12px;display:flex;justify-content:flex-end;gap:8px}.aics-actions button,.aics-actions a{padding:9px 14px;border-radius:4px;text-decoration:none}.aics-actions button{border:0;background:#166534;color:#fff}.aics-actions a{background:#374151;color:#fff}@media print{.aics-editor{padding:0;background:#fff}.aics-actions{display:none}.aics-page{box-shadow:none;margin:0;page-break-after:always}.aics-page:last-child{page-break-after:auto}}
    </style>

    @if(session('success'))<div class="mx-auto mt-4 max-w-3xl rounded border border-green-200 bg-green-50 p-3 text-green-800">{{ session('success') }}</div>@endif
    <form method="POST" action="{{ route('beneficiaries.application-form.update', $beneficiary) }}" class="aics-editor">
        @csrf @method('PUT')
        <div class="aics-actions"><a target="_blank" href="{{ route('beneficiaries.application-form', $beneficiary) }}">Review PDF</a><button type="submit">Save Final AICS Form</button></div>

        <section class="aics-page">
            <div class="aics-official">Republic of the Philippines<div class="municipality">MUNICIPALITY OF TAGUDIN</div>Province of Ilocos Sur<div class="office">OFFICE OF THE<br>MUNICIPAL SOCIAL WELFARE AND DEVELOPMENT OFFICER</div></div>
            <div style="margin-top:30px;font-size:10px"><b>BA FORM 206</b><br>Series of 1986<br><b>CN:</b> {{ $beneficiary->reference_code }}</div>
            <div class="aics-title">CERTIFICATE OF ELIGIBILITY</div>
            <p>This is to certify that <input class="aics-line aics-wide" value="{{ $client }}" readonly> of Barangay <input class="aics-line aics-medium" value="{{ $barangay }}" readonly>, Ilocos Sur has been found eligible for <input class="aics-line aics-medium" value="{{ $assistance }}" readonly> under the Bureau of Emergency Assistance.</p>
            <p>Client is recommended for assistance in the amount of <input class="aics-line aics-medium" value="Php {{ number_format((float) $beneficiary->amount, 2) }}" readonly>.</p>
            <div style="margin-top:90px;width:48%;border-top:1px solid #111;text-align:center;padding-top:5px">Municipal Social Welfare and Development Officer</div>
            <div style="margin:70px 0 0 52%;width:48%;border-top:1px solid #111;text-align:center;padding-top:5px">Municipal Mayor</div>
        </section>

        <section class="aics-page">
            <div class="aics-official">Republic of the Philippines<div class="municipality">MUNICIPALITY OF TAGUDIN</div>Province of Ilocos Sur<div class="office">OFFICE OF THE MUNICIPAL SOCIAL WELFARE AND DEVELOPMENT OFFICER<br>BUREAU OF EMERGENCY ASSISTANCE</div></div>
            <div class="aics-application-title">APPLICATION FORM</div>
            <div class="aics-row"><input type="hidden" name="resettled" value="0"><input type="checkbox" name="resettled" value="1" @checked($value('resettled'))> Resettled <span style="float:right"><input type="checkbox" @checked(!$value('resettled')) disabled> Not Resettled</span></div>
            <div class="aics-row"><b>Disaster Victim:</b> ND <input type="radio" name="disaster_victim" value="ND" @checked($value('disaster_victim') === 'ND')> MD <input type="radio" name="disaster_victim" value="MD" @checked($value('disaster_victim') === 'MD')></div>
            <div class="aics-row"><b>Date of Occurrence:</b> <input type="date" name="date_of_occurrence" value="{{ $value('date_of_occurrence') }}" class="aics-line aics-medium"></div>
            <div class="aics-row"><b>Type of Assistance:</b> <input class="aics-line aics-wide" value="{{ $assistance }}" readonly></div>
            <div class="aics-row"><b>FAMILY HEAD:</b> <input class="aics-line aics-wide" value="{{ $client }}" readonly><br><b>Address:</b> <input class="aics-line aics-wide" value="{{ $barangay }}, Tagudin, Ilocos Sur" readonly></div>
            <table class="aics-table"><tr><th>NAME</th><th>RELATION TO CLIENT</th><th>AGE</th></tr><tr><td>{{ $client }}</td><td>Client</td><td>{{ $beneficiary->age }}</td></tr>@for($i=1;$i<6;$i++)<tr><td>&nbsp;</td><td></td><td></td></tr>@endfor</table>
            <div class="aics-row"><b>Estimated Damaged:</b> <input name="estimated_damage" value="{{ $value('estimated_damage') }}" class="aics-line aics-medium"><br><b>CASUALTIES — NAME:</b> <input name="casualty_name" value="{{ $value('casualty_name') }}" class="aics-line aics-wide"></div>
            <div style="margin:70px auto 0;width:280px;border-top:1px solid #111;text-align:center;padding-top:5px"><input name="client_signature_name" value="{{ $value('client_signature_name') }}" class="aics-line aics-full" placeholder="Client signature name"></div>
        </section>

        <section class="aics-page">
            <div class="aics-official">Republic of the Philippines<div class="municipality">MUNICIPALITY OF TAGUDIN</div>Province of Ilocos Sur<div class="office">MUNICIPAL SOCIAL WELFARE AND DEVELOPMENT OFFICER</div></div>
            <div style="margin-top:160px;text-align:center">Date Registered: <input class="aics-line aics-medium" value="{{ $beneficiary->date_of_assistance?->format('F d, Y') }}" readonly><div style="margin-top:20px">To be attached to the voucher</div></div>
            <div class="aics-receipt" style="margin-top:100px"><b>REIMBURSEMENT EXPENSE RECEIPT</b><div class="aics-section">RECEIPT DETAILS</div>
                Date: <input type="date" name="receipt_date" value="{{ $value('receipt_date') }}" class="aics-line aics-small"> No.: <input name="receipt_number" value="{{ $value('receipt_number') }}" class="aics-line aics-small"><br><br>
                RECEIVED from <input class="aics-line aics-medium" value="{{ $client }}" readonly><br><br>
                Amount: <input type="number" step="0.01" min="0" name="receipt_amount" value="{{ $value('receipt_amount') }}" class="aics-line aics-medium"><br><br>
                Payment for: <input name="receipt_purpose" value="{{ $value('receipt_purpose', $assistance) }}" class="aics-line aics-wide"><div style="margin-top:60px"><b>PAYEE</b><br>Name: <input name="payee_name" value="{{ $value('payee_name') }}" class="aics-line aics-medium"><br>Address: <input name="payee_address" value="{{ $value('payee_address') }}" class="aics-line aics-medium"><br>Tax Cert. No.: <input name="payee_tax_certificate" value="{{ $value('payee_tax_certificate') }}" class="aics-line aics-small"><br>Date of Issue: <input type="date" name="payee_date_of_issue" value="{{ $value('payee_date_of_issue') }}" class="aics-line aics-small"><br>Place of Issue: <input name="payee_place_of_issue" value="{{ $value('payee_place_of_issue') }}" class="aics-line aics-medium"><br><br>Witness: <input name="witness_name" value="{{ $value('witness_name') }}" class="aics-line aics-medium"></div>
            </div>
        </section>
    </form>
</x-app-layout>
