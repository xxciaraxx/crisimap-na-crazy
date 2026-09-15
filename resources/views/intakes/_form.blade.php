@php
    $value = fn ($name, $fallback = '') => old($name, isset($intake) ? ($intake->{$name} ?? $fallback) : $fallback);
    $clientSectors = old('client_sectors', isset($intake) ? ($intake->client_sectors ?? []) : []);
    $incomeResources = old('income_resources', isset($intake) ? ($intake->income_resources ?? []) : []);
    $submittedDocs = old('supporting_documents', isset($intake) ? ($intake->supporting_documents ?? []) : []);
    $family = old('family_composition', isset($intake) ? ($intake->family_composition ?? []) : []);
    $family = array_pad(is_array($family) ? $family : [], 6, []);
@endphp

@if($errors->any())
    <div class="gis-error" role="alert">
        <strong>Please check the following General Intake Sheet information:</strong>
        <ul>
            @foreach($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif

<style>
    .gis-page { max-width: 960px; margin: 0 auto; padding: clamp(12px, 2vw, 24px); background: #fff; border: 1px solid #dce7f2; border-top: 4px solid #1768aa; border-radius: 14px; box-shadow: 0 12px 30px rgba(15, 56, 94, .08); }
    .gis-editor { max-width: 210mm; margin: 0 auto; color: #203b58; font: 10px/1.3 Arial, Helvetica, sans-serif; }
    .gis-editor *, .gis-editor *::before, .gis-editor *::after { box-sizing: border-box; }
    .gis-letterhead { display: grid; grid-template-columns: 25% 50% 25%; align-items: center; min-height: 74px; padding: 8px 2px; text-align: center; margin-bottom: 8px; background: linear-gradient(180deg, #f7fbff, #fff); border-bottom: 1px solid #dbe8f4; }
    .gis-letterhead > * { min-width: 0; }
    .gis-letterhead-marks { display: flex; align-items: center; justify-content: space-evenly; gap: 8px; }
    .gis-letterhead img { display: block; margin: 0 auto; object-fit: contain; }
    .gis-seal { width: 64px; height: 64px; } .gis-asap { width: 96px; height: 50px; }
    .gis-heading { align-self: center; line-height: 1.2; font-size: 10px; } .gis-heading strong { display: block; margin-top: 3px; font-size: 11px; text-transform: uppercase; }
    .gis-office { margin: 0 0 8px; color: #123e68; text-align: center; font-size: 13px; font-weight: 700; line-height: 1.05; letter-spacing: .1px; }
    .gis-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .gis-table td, .gis-table th { border: 1px solid #bfd0e0; padding: 5px 6px; vertical-align: top; }
    .gis-title { padding: 8px !important; color: #fff; background: #174f7d; text-align: center; font-size: 14px; letter-spacing: .04em; } .gis-section { padding: 5px 6px !important; color: #174f7d; background: #eef6fd; font-size: 10px; letter-spacing: .03em; }
    .gis-tight { line-height: 1.45; }
    .gis-editor input:not([type=checkbox]):not([type=radio]), .gis-editor select, .gis-editor textarea { border: 0; border-bottom: 1px solid #8eabc4; border-radius: 3px 3px 0 0; background: #f8fbfe; box-shadow: none; color: #203b58; font: inherit; padding: 2px 4px; transition: border-color .15s, background .15s; }
    .gis-editor input:not([type=checkbox]):not([type=radio]):focus, .gis-editor select:focus, .gis-editor textarea:focus { outline: 0; border-bottom-color: #1670b8; background: #eef7ff; }
    .gis-editor input:not([type=checkbox]):not([type=radio]), .gis-editor select { height: 16px; width: 100%; }
    .gis-editor textarea { display: block; width: 100%; resize: vertical; min-height: 24px; }
    .gis-editor input[type=checkbox], .gis-editor input[type=radio] { width: 12px; height: 12px; margin: 0 3px 0 0; vertical-align: -2px; accent-color: #111; }
    .gis-inline { display: inline-flex; align-items: baseline; gap: 2px; } .gis-inline input, .gis-inline select { width: auto !important; min-width: 42px; }
    .gis-wide { min-width: 135px !important; } .gis-medium { min-width: 90px !important; } .gis-small { min-width: 45px !important; }
    .gis-family th { text-align: center; font-size: 8px; } .gis-family td { padding: 0; height: 22px; }
    .gis-family input, .gis-family select { height: 21px !important; border: 0 !important; padding: 1px 2px !important; }
    .gis-box-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; } .gis-box-grid > div { min-height: 100px; padding: 6px 7px; border-right: 1px solid #bfd0e0; } .gis-box-grid > div:last-child { border-right: 0; }
    .gis-checklist { line-height: 1.55; } .gis-checklist label { display: block; } .gis-mini { font-size: 8px; }
    .gis-signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; } .gis-error { margin-bottom: 8px; border: 1px solid #b91c1c; padding: 8px; color: #991b1b; } .gis-error strong { display: block; margin-bottom: 4px; } .gis-error ul { margin: 0; padding-left: 20px; } .gis-error li + li { margin-top: 2px; } .gis-autosave-status { color: #64748b; font-size: 10px; }
    .gis-actions { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 16px; padding-top: 14px; border-top: 1px solid #e2e8f0; }
    .gis-action-group { display: flex; align-items: center; gap: 8px; }
    .gis-action { display: inline-flex; align-items: center; justify-content: center; min-height: 38px; border: 1px solid transparent; padding: 9px 15px; cursor: pointer; font: 600 13px/1 Arial, Helvetica, sans-serif; text-decoration: none; white-space: nowrap; }
    .gis-action-save { background: #166534; color: #fff; } .gis-action-save:hover { background: #14532d; }
    .gis-action-print { border-color: #cbd5e1; background: #fff; color: #334155; } .gis-action-print:hover { background: #f8fafc; }
    .gis-action-submit { background: #1d4ed8; color: #fff; } .gis-action-submit:hover { background: #1e40af; }
    .gis-client-name-wrap { position: relative; display: inline-block; width: min(100%, 540px); }
    .gis-applicant-history-card { position: absolute; z-index: 20; left: 0; top: calc(100% + 8px); width: min(100%, 360px); background: #fff; color: #1e3a5f; border: 1px solid #bfdbfe; border-top: 4px solid #1565c0; border-radius: 10px; box-shadow: 0 14px 32px rgba(21, 101, 192, 0.2); padding: 12px 14px; font-size: 11px; line-height: 1.45; }
    .gis-applicant-history-card[hidden] { display: none; }
    .gis-applicant-history-card strong { display: block; margin-bottom: 3px; color: #0b3d78; font-size: 12px; }
    .gis-applicant-history-card .gis-history-pill { display: inline-block; margin-bottom: 6px; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
    .gis-applicant-history-card .gis-history-pill.is-returning { background: #dbeafe; color: #075985; }
    .gis-applicant-history-card .gis-history-pill.is-new { background: #e0f2fe; color: #0369a1; }
    .gis-applicant-history-card ul { margin: 8px 0 0; padding: 7px 0 0 18px; border-top: 1px solid #dbeafe; }
    .gis-applicant-history-card li + li { margin-top: 2px; }
    .gis-upload-field { display: grid; gap: 3px; margin-top: 8px; border: 1px dashed #a9c3da; border-radius: 6px; padding: 7px; color: #315775; background: #f5faff; }
    .gis-upload-field span { font-weight: 700; }
    .gis-upload-field input[type=file] { width: 100%; height: auto; border: 0; background: transparent; padding: 3px 0; }
    .gis-upload-field small { color: #71879c; font-size: 8px; }
    .gis-uploaded-files { display: grid; gap: 5px; margin-top: 7px; padding-top: 5px; border-top: 1px solid #dbe8f4; }
    .gis-uploaded-files b { color: #315775; }
    .gis-uploaded-file { display: flex; align-items: center; gap: 6px; min-width: 0; }
    .gis-uploaded-file > span { overflow: hidden; flex: 1; color: #536f89; text-overflow: ellipsis; white-space: nowrap; }
    .gis-attachment-view-button, .gis-attachment-download-button { flex: 0 0 auto; border-radius: 4px; padding: 3px 6px; text-decoration: none; white-space: nowrap; }
    .gis-attachment-view-button { color: #fff !important; background: #1768aa; }
    .gis-attachment-download-button { color: #315775 !important; background: #eaf2f9; }
    .gis-no-attachments { margin: 0; color: #71879c; font-size: 8px; }
    .gis-zoom-toolbar { display: flex; align-items: center; justify-content: flex-end; gap: 4px; max-width: 210mm; margin: 0 auto 12px; color: #607a94; font: 600 11px/1 Arial, Helvetica, sans-serif; }
    .gis-zoom-label { margin-right: 5px; color: #40617e; font-weight: 700; }
    .gis-zoom-label > span { color: #1670b8; font-size: 12px; }
    .gis-zoom-toolbar button, .gis-zoom-toolbar output { display: inline-grid; place-items: center; min-width: 30px; height: 30px; border: 1px solid #cbdbea; border-radius: 6px; background: #fff; color: #315775; font: inherit; }
    .gis-zoom-toolbar button { cursor: pointer; }
    .gis-zoom-toolbar button:hover, .gis-zoom-toolbar button:focus-visible { border-color: #5b9bd0; background: #eef7ff; outline: none; }
    .gis-zoom-toolbar output { min-width: 48px; border-color: transparent; background: #f2f7fb; color: #174f7d; font-size: 10px; }
    .gis-zoom-toolbar button[data-gis-zoom="reset"] { width: auto; margin-left: 3px; padding: 0 8px; font-size: 10px; }
    .gis-zoom-toolbar button:disabled { cursor: not-allowed; opacity: .45; }
    @media print { @page { size: A4 portrait; margin: 6mm 8mm 5mm; } body { background: white !important; } .gis-page { width: 194mm; min-height: 286mm; padding: 0; border: 0; box-shadow: none; max-width: none; margin: 0 auto; } .gis-editor { width: 194mm; min-height: 286mm; max-width: none; margin: 0 auto; zoom: 1 !important; } .gis-actions, .gis-error, .gis-zoom-toolbar { display: none; } }
    @media (max-width: 800px) { .gis-page { padding: 10px; border-left: 0; border-right: 0; box-shadow: none; } .gis-editor { min-width: 680px; } }
</style>

<div class="gis-editor">
    <div class="gis-letterhead">
        <div class="gis-letterhead-marks">
            <img src="{{ asset('images/intake-tagudin-seal-white.png') }}" class="gis-seal" alt="Bayan ng Tagudin seal">
            <img src="{{ asset('images/intake-asap-seal-white.png') }}" class="gis-asap" alt="ASAP Tagudin seal">
        </div>
        <div class="gis-heading">Republic of the Philippines<br>Province of Ilocos Sur<strong>Municipality of Tagudin</strong></div>
        <img src="{{ asset('images/intake-national-seal.png') }}" class="gis-seal" alt="DSWD logo">
    </div>
    <div class="gis-office">OFFICE OF THE MUNICIPAL SOCIAL WELFARE AND DEVELOPMENT OFFICER</div>
    <table class="gis-table">
        <tr><th colspan="3" class="gis-title">GENERAL INTAKE SHEET</th></tr>
        <tr><td style="width:35%" class="gis-checklist"><label><input type="radio" name="program" value="AICS" @checked($value('program', 'AICS') === 'AICS')>AICS</label><label><input type="radio" name="program" value="ESA" @checked($value('program') === 'ESA')>ESA</label><label><input type="radio" name="program" value="Others" @checked($value('program') === 'Others')>OTHERS: <input class="gis-medium" name="program_other" value="{{ old('program_other') }}"></label></td><td style="width:25%" class="gis-checklist"><label><input type="radio" name="client_type" value="new" @checked($value('client_type', 'new') === 'new')> NEW</label><label><input type="radio" name="client_type" value="returning" @checked($value('client_type') === 'returning')> RETURNING</label></td><td style="width:40%" class="gis-tight">DATE: <input class="gis-wide" required type="date" name="intake_date" value="{{ $value('intake_date', now()->toDateString()) }}"><br>CASE NUMBER: <span class="gis-line gis-wide">{{ $intake->intake_number ?? 'To be assigned' }}</span></td></tr>
        <tr><td colspan="3" class="gis-tight"><b>CLIENT'S NAME:</b> <div class="gis-client-name-wrap"><input class="gis-wide" required name="client_name" id="intake-client-name" autocomplete="off" value="{{ $value('client_name') }}"><div id="applicant-history-card" class="gis-applicant-history-card" hidden></div></div><br><span class="gis-inline">Age: <input class="gis-small" type="number" name="age" min="0" max="120" value="{{ $value('age') }}"></span> <span class="gis-inline">Sex: <select name="sex"><option value="">____</option>@foreach(['Male','Female','Other'] as $sex)<option value="{{ $sex }}" @selected($value('sex') === $sex)>{{ $sex }}</option>@endforeach</select></span> <span class="gis-inline">Civil Status: <select class="gis-medium" name="civil_status"><option value="">____</option>@foreach(['Single','Married','Widowed'] as $civilStatus)<option value="{{ $civilStatus }}" @selected($value('civil_status') === $civilStatus)>{{ $civilStatus }}</option>@endforeach</select></span> <span class="gis-inline">Occupation: <input class="gis-medium" name="occupation" value="{{ $value('occupation') }}"></span> <span class="gis-inline">Monthly Income: Php <input class="gis-small" type="number" step=".01" name="monthly_income" value="{{ $value('monthly_income') }}"></span><br>Address: Brgy. <select class="gis-wide" name="barangay_id"><option value="">Select</option>@foreach($barangays as $barangay)<option value="{{ $barangay->id }}" @selected($value('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>@endforeach</select>, Tagudin, Ilocos Sur <input type="hidden" name="address" value="{{ $value('address') }}"></td></tr>
        <tr><td colspan="3" class="gis-tight"><b>BENEFICIARY'S NAME:</b> <input type="checkbox" id="same-as-client-name" @checked($value('beneficiary_name') === '')> SAME AS ABOVE <div class="gis-client-name-wrap"><input class="gis-wide" name="beneficiary_name" id="intake-beneficiary-name" autocomplete="off" value="{{ $value('beneficiary_name') }}"><div id="beneficiary-history-card" class="gis-applicant-history-card" hidden></div></div></td></tr>
        <tr><td colspan="2">Purpose of Assistance: <select class="gis-wide" id="purpose-of-assistance" name="purpose_of_assistance"><option value="">Select assistance type</option>@foreach(($assistanceTypes ?? []) as $type)<option value="{{ $type->name }}" @selected($value('purpose_of_assistance') === $type->name)>{{ $type->name }}</option>@endforeach</select><span id="assistance-specification-wrap" class="gis-inline" style="display:none;margin-left:6px;">Specify: <input class="gis-wide" name="assistance_specification" value="{{ $value('assistance_specification') }}"></span></td><td>Amount: Php <input class="gis-medium" type="number" step=".01" name="requested_amount" value="{{ $value('requested_amount') }}"></td></tr>
        <tr><td colspan="3">Diagnosis/Cause of Death (if burial): <input class="gis-wide" name="diagnosis_or_cause_of_death" value="{{ $value('diagnosis_or_cause_of_death') }}"></td></tr>
        <tr><th colspan="3" class="gis-section">FAMILY COMPOSITION</th></tr>
        <tr><td colspan="3" style="padding:0"><table class="gis-table gis-family"><tr><th style="width:22%">NAME</th><th style="width:12%">RELATION<br>TO CLIENT</th><th style="width:6%">AGE</th><th style="width:6%">SEX</th><th style="width:10%">CIVIL<br>STATUS</th><th style="width:16%">EDUCATIONAL<br>ATTAINMENT</th><th style="width:10%">OCCUPATION</th><th style="width:8%">INCOME</th><th style="width:10%">REMARKS</th></tr>@foreach($family as $index => $member)<tr><td><input name="family_composition[{{ $index }}][name]" value="{{ $member['name'] ?? '' }}"></td><td><input name="family_composition[{{ $index }}][relationship_to_client]" value="{{ $member['relationship_to_client'] ?? '' }}"></td><td><input name="family_composition[{{ $index }}][age]" value="{{ $member['age'] ?? '' }}"></td><td><input name="family_composition[{{ $index }}][sex]" value="{{ $member['sex'] ?? '' }}"></td><td><select name="family_composition[{{ $index }}][civil_status]"><option value="">-</option>@foreach(['Single','Married','Widowed'] as $civilStatus)<option value="{{ $civilStatus }}" @selected(($member['civil_status'] ?? '') === $civilStatus)>{{ $civilStatus }}</option>@endforeach</select></td><td><input name="family_composition[{{ $index }}][educational_attainment]" value="{{ $member['educational_attainment'] ?? '' }}"></td><td><input name="family_composition[{{ $index }}][occupation]" value="{{ $member['occupation'] ?? '' }}"></td><td><input name="family_composition[{{ $index }}][income]" value="{{ $member['income'] ?? '' }}"></td><td><input name="family_composition[{{ $index }}][remarks]" value="{{ $member['remarks'] ?? '' }}"></td></tr>@endforeach</table></td></tr>
        <tr><td colspan="3" style="padding:0"><div class="gis-box-grid"><div><b>CLIENT'S SECTOR</b><div class="gis-checklist">@foreach(['Solo Parent','WEDC','Senior Citizens','4Ps Beneficiary','PWDs','No Regular Income','Others'] as $sector)<label><input type="checkbox" name="client_sectors[]" value="{{ $sector }}" @checked(in_array($sector, $clientSectors))>{{ $sector }}</label>@endforeach</div></div><div><b>INCOME AND FINANCIAL RESOURCES</b><div class="gis-checklist"><label><input type="checkbox" name="income_resources[]" value="Employed" @checked(in_array('Employed', $incomeResources))>Employed</label><span class="gis-mini">(Indicate number of members working)</span><input type="number" name="employed_members_count" value="{{ $value('employed_members_count') }}"><label><input type="checkbox" name="income_resources[]" value="Seasonal" @checked(in_array('Seasonal', $incomeResources))>Seasonal</label><span class="gis-mini">(Indicate number of members working)</span><input type="number" name="seasonal_members_count" value="{{ $value('seasonal_members_count') }}"><label><input type="checkbox" name="income_resources[]" value="Combined family income" @checked(filled($value('combined_family_income')))>Combined family income</label><input type="number" step=".01" name="combined_family_income" value="{{ $value('combined_family_income') }}"> Php/month</div></div><div><b>BUDGET AND EXPENSES</b><br>Monthly expenses of the family<br>Php <input type="number" step=".01" name="monthly_expenses" value="{{ $value('monthly_expenses') }}"><p class="gis-mini">(Utility bills, Maintenance, Medication, Mortgage/Rent, Debt and others)</p></div></div></td></tr>
        <tr><td colspan="3"><b>SUPPORTING DOCUMENTS SUBMITTED</b><div class="gis-box-grid"><div class="gis-checklist">@foreach(['Valid ID','Certificate of Indigency','Medical Certificate','Medical Abstract','Hospital Billing Statement'] as $doc)<label><input type="checkbox" name="supporting_documents[]" value="{{ $doc }}" @checked(in_array($doc, $submittedDocs))>{{ $doc }}</label>@endforeach</div><div class="gis-checklist">@foreach(['Prescription','Imaging Request','Laboratory Request','Funeral Contract','Death Certificate'] as $doc)<label><input type="checkbox" name="supporting_documents[]" value="{{ $doc }}" @checked(in_array($doc, $submittedDocs))>{{ $doc }}</label>@endforeach</div><div class="gis-checklist">@foreach(['Enrollment Fee','Certificate of Enrollment','Fare Ticket','Letter of Request','Barangay Certification','Certificate of Residency','Others'] as $doc)<label><input type="checkbox" name="supporting_documents[]" value="{{ $doc }}" @checked(in_array($doc, $submittedDocs))>{{ $doc }}</label>@endforeach</div></div><label class="gis-upload-field"><span>Attach scanned documents</span><input type="file" id="intake-attachments" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"><small>JPG, PNG, PDF, DOC, or DOCX · maximum 5 MB each</small></label><div id="selected-attachments" class="gis-uploaded-files"><b>Attachments</b><p class="gis-no-attachments">No attachments selected yet.</p></div>@if(isset($intake) && $intake->attachments->isNotEmpty())<div class="gis-uploaded-files"><b>Previously attached</b>@foreach($intake->attachments as $attachment)<div class="gis-uploaded-file"><span title="{{ $attachment->name }}">{{ $attachment->name }}</span><a class="gis-attachment-view-button" href="{{ route('attachments.view', $attachment) }}" target="_blank" rel="noopener">View attachment</a><a class="gis-attachment-download-button" href="{{ route('attachments.download', $attachment) }}">Download</a></div>@endforeach</div>@endif</td></tr>
        <tr><td colspan="3"><b>SOCIAL WORKER'S ASSESSMENT</b><textarea name="social_worker_assessment" rows="4">{{ $value('social_worker_assessment', "Client's family belongs to the less fortunate member of the community having no fix source of income. They have limited financial resources that could hardly enough to suffice their everyday needs. Family is emotionally and financially depressed brought about their present situation. Relatives couldn't help them financially as they are also experiencing financial problem, thus client came here for augmentation. Therefore, client is hereby recommended for financial assistance to help family lessen with their present crisis situation.") }}</textarea></td></tr>
        <tr><td colspan="3"><b>CERTIFICATION</b><br>I certify that the information I have provided is true, complete and correct to the best of my knowledge. I understand that any false or misleading information may affect my eligibility for financial assistance.<br><br><div class="gis-signatures"><label>Applicant's Signature: <input name="applicant_signature" value=""><br>Printed Name: <input value="{{ $value('client_name') }}" readonly><br>Contact Number: <input name="contact" value="{{ $value('contact') }}"></label><label>Interviewer Signature: <input name="interviewer_signature" value=""><br>Printed Name: <input name="interviewer_name" value="{{ $value('interviewer_name', auth()->user()->name) }}"><br>Position: <input name="interviewer_position" value="{{ $value('interviewer_position', 'Social Officer') }}"></label></div></td></tr>
    </table>
</div>
<div class="gis-actions">
    <button class="gis-action gis-action-save" type="submit" name="action" value="save">Save as Draft</button>
    <span id="intake-autosave-status" class="gis-autosave-status" role="status" aria-live="polite"></span>
    <div class="gis-action-group">
        @if(isset($intake) && $intake->exists)
            <a class="gis-action gis-action-print" href="{{ route('intakes.print', $intake) }}" title="Open print preview">Print</a>
            <button class="gis-action gis-action-submit" type="submit" name="action" value="review">Review &amp; submit</button>
        @else
            <button class="gis-action gis-action-print" type="submit" name="action" value="preview" title="Open print preview">Print</button>
            <button class="gis-action gis-action-submit" type="submit" name="action" value="review">Review &amp; submit</button>
        @endif
    </div>
</div>
<script>
    (() => {
        const endpoint = @json(route('intakes.applicant-history'));
        const form = document.querySelector('.gis-editor')?.closest('form');
        const autosaveStatus = document.getElementById('intake-autosave-status');
        const autosaveKey = `crisimap.intake.autosave.${window.location.pathname}`;
        let autosaveTimer;

        const zoomStorageKey = 'crisimap.general-intake-form-zoom';
        const zoomSteps = [0.8, 0.9, 1, 1.1, 1.2, 1.3, 1.4];
        const zoomControls = document.querySelectorAll('[data-gis-zoom-controls]');
        const editors = document.querySelectorAll('.gis-editor');
        let zoom = 1;
        try {
            zoom = Number.parseFloat(localStorage.getItem(zoomStorageKey) || '1');
        } catch (_) {
            zoom = 1;
        }
        if (!zoomSteps.includes(zoom)) zoom = 1;

        const renderZoom = () => {
            editors.forEach((editor) => { editor.style.zoom = String(zoom); });
            zoomControls.forEach((toolbar) => {
                const value = toolbar.querySelector('[data-gis-zoom-value]');
                if (value) value.textContent = `${Math.round(zoom * 100)}%`;
                const decrease = toolbar.querySelector('[data-gis-zoom="out"]');
                const increase = toolbar.querySelector('[data-gis-zoom="in"]');
                if (decrease) decrease.disabled = zoom === zoomSteps[0];
                if (increase) increase.disabled = zoom === zoomSteps[zoomSteps.length - 1];
            });
        };

        zoomControls.forEach((toolbar) => {
            toolbar.addEventListener('click', (event) => {
                const button = event.target.closest('button[data-gis-zoom]');
                if (!button) return;
                const action = button.dataset.gisZoom;
                const currentIndex = zoomSteps.indexOf(zoom);
                zoom = action === 'reset'
                    ? 1
                    : zoomSteps[Math.min(zoomSteps.length - 1, Math.max(0, currentIndex + (action === 'in' ? 1 : -1)))];
                try {
                    localStorage.setItem(zoomStorageKey, String(zoom));
                } catch (_) {
                    // Zoom still works for the current page when browser storage is unavailable.
                }
                renderZoom();
            });
        });
        renderZoom();

        const attachmentInput = document.getElementById('intake-attachments');
        const selectedAttachments = document.getElementById('selected-attachments');
        let attachmentPreviewUrls = [];

        const renderSelectedAttachments = () => {
            if (!attachmentInput || !selectedAttachments) return;
            attachmentPreviewUrls.forEach((url) => URL.revokeObjectURL(url));
            attachmentPreviewUrls = [];
            selectedAttachments.innerHTML = '<b>Attachments</b>';

            if (!attachmentInput.files.length) {
                selectedAttachments.insertAdjacentHTML('beforeend', '<p class="gis-no-attachments">No attachments selected yet.</p>');
                return;
            }

            [...attachmentInput.files].forEach((file) => {
                const url = URL.createObjectURL(file);
                attachmentPreviewUrls.push(url);
                const row = document.createElement('div');
                row.className = 'gis-uploaded-file';
                row.innerHTML = `<span title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</span><a class="gis-attachment-view-button" href="${url}" target="_blank" rel="noopener">View attachment</a>`;
                selectedAttachments.appendChild(row);
            });
        };

        attachmentInput?.addEventListener('change', renderSelectedAttachments);

        const setAutosaveStatus = (message) => {
            if (autosaveStatus) autosaveStatus.textContent = message;
        };

        const saveFormLocally = () => {
            if (!form) return;
            const values = {};
            form.querySelectorAll('input, select, textarea').forEach((field) => {
                if (!field.name || field.disabled) return;
                if (field.type === 'file' || field.type === 'hidden') return;
                if (field.type === 'checkbox' || field.type === 'radio') {
                    if (!values[field.name]) values[field.name] = [];
                    if (field.checked) values[field.name].push(field.value || 'on');
                    return;
                }
                if (field.name.endsWith('[]')) {
                    if (!values[field.name]) values[field.name] = [];
                    if (field.value) values[field.name].push(field.value);
                    return;
                }
                values[field.name] = field.value;
            });

            try {
                localStorage.setItem(autosaveKey, JSON.stringify({ values, savedAt: Date.now() }));
                setAutosaveStatus('Saved locally just now');
            } catch (_) {
                setAutosaveStatus('Local auto-save is unavailable');
            }
        };

        const restoreFormLocally = () => {
            if (!form) return;
            try {
                const saved = JSON.parse(localStorage.getItem(autosaveKey) || 'null');
                if (!saved?.values) return;
                Object.entries(saved.values).forEach(([name, value]) => {
                    const fields = [...form.querySelectorAll(`[name="${CSS.escape(name)}"]`)];
                    fields.forEach((field) => {
                        if (field.type === 'checkbox' || field.type === 'radio') {
                            field.checked = Array.isArray(value) && value.includes(field.value || 'on');
                        } else {
                            field.value = Array.isArray(value) ? value[0] || '' : value;
                        }
                    });
                });
                form.querySelectorAll('input, select, textarea').forEach((field) => field.dispatchEvent(new Event('change', { bubbles: true })));
                setAutosaveStatus('Restored locally saved changes');
            } catch (_) {
                localStorage.removeItem(autosaveKey);
            }
        };

        if (form && !form.closest('fieldset[disabled]')) {
            restoreFormLocally();
            form.addEventListener('input', () => {
                clearTimeout(autosaveTimer);
                setAutosaveStatus('Saving locally...');
                autosaveTimer = setTimeout(saveFormLocally, 500);
            });
            form.addEventListener('change', () => {
                clearTimeout(autosaveTimer);
                autosaveTimer = setTimeout(saveFormLocally, 100);
            });
            form.addEventListener('submit', () => localStorage.removeItem(autosaveKey));
        }

        const clientTypeReturning = document.querySelector('input[name="client_type"][value="returning"]');
        const escapeHtml = (value) => {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        };

        const attachHistoryLookup = (inputId, cardId) => {
            const input = document.getElementById(inputId);
            const card = document.getElementById(cardId);
            if (!input || !card) return;
            let timer;

            const renderCard = (data) => {
                if (!data || !data.name) {
                    card.hidden = true;
                    return;
                }
                const isReturning = Boolean(data.found);
                if (isReturning && clientTypeReturning) {
                    clientTypeReturning.checked = true;
                }
                const entries = data.applications || [];
                const listHtml = entries.length
                    ? `<ul>${entries.map(item => `<li><strong>${escapeHtml(item.assistance_type)}</strong> — ${escapeHtml(item.date)} (${escapeHtml(item.status)})</li>`).join('')}</ul>`
                    : '<p>No prior assistance record found.</p>';
                card.innerHTML = `
                    <span class="gis-history-pill ${isReturning ? 'is-returning' : 'is-new'}">${isReturning ? 'Returning applicant' : 'New applicant'}</span>
                    <strong>${escapeHtml(data.name)}</strong>
                    <div>Total records: <b>${data.application_count}</b></div>
                    ${listHtml}
                `;
                card.hidden = false;
            };

            input.addEventListener('input', () => {
                clearTimeout(timer);
                const value = input.value.trim();
                if (value.length < 2) {
                    card.hidden = true;
                    return;
                }
                timer = setTimeout(async () => {
                    try {
                        const response = await fetch(`${endpoint}?name=${encodeURIComponent(value)}`, { headers: { Accept: 'application/json' } });
                        if (!response.ok) {
                            card.hidden = true;
                            return;
                        }
                        renderCard(await response.json());
                    } catch (_) {
                        card.hidden = true;
                    }
                }, 300);
            });
            input.addEventListener('focus', () => {
                if (input.value.trim().length >= 2 && !card.hidden) card.hidden = false;
            });
            input.addEventListener('blur', () => {
                setTimeout(() => card.hidden = true, 150);
            });
        };

        attachHistoryLookup('intake-client-name', 'applicant-history-card');
        attachHistoryLookup('intake-beneficiary-name', 'beneficiary-history-card');

        const purposeOfAssistance = document.getElementById('purpose-of-assistance');
        const assistanceSpecificationWrap = document.getElementById('assistance-specification-wrap');
        if (purposeOfAssistance && assistanceSpecificationWrap) {
            const toggleAssistanceSpecification = () => {
                const isOther = /other|miscellaneous/i.test(purposeOfAssistance.value);
                assistanceSpecificationWrap.style.display = isOther ? 'inline-flex' : 'none';
            };
            purposeOfAssistance.addEventListener('change', toggleAssistanceSpecification);
            toggleAssistanceSpecification();
        }

        const clientName = document.getElementById('intake-client-name');
        const beneficiaryName = document.getElementById('intake-beneficiary-name');
        const sameAsClientName = document.getElementById('same-as-client-name');

        if (clientName && beneficiaryName && sameAsClientName) {
            const syncBeneficiaryName = () => {
                if (sameAsClientName.checked) {
                    beneficiaryName.value = clientName.value;
                    beneficiaryName.readOnly = true;
                } else {
                    beneficiaryName.readOnly = false;
                }
            };

            sameAsClientName.addEventListener('change', syncBeneficiaryName);
            clientName.addEventListener('input', syncBeneficiaryName);
            syncBeneficiaryName();
        }
    })();
</script>
