@php
    $analytics = $summary['assistance_analytics'];
    $barangayAnalytics = $summary['barangay_analytics'];
    $total = max((int) $summary['total'], 1);
    $maxBarangayCount = max((int) ($barangayAnalytics->max('count') ?? 0), 1);
    $circumference = 100;
    $offset = 0;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>CRISIMAP Report</title>
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #1f2937; }
        .sheet { width: 100%; }
        .letterhead { width: 100%; border-collapse: collapse; margin-bottom: 6px; table-layout: fixed; }
        .letterhead td { height: 72px; padding: 0; vertical-align: middle; text-align: center; }
        .letterhead td:first-child, .letterhead td:last-child { width: 24%; }
        .letterhead td:nth-child(2) { width: 52%; }
        .logo { height: 60px; width: auto; object-fit: contain; }
        .logo.asap { height: 46px; max-width: 88px; margin-left: 6px; }
        .head-small { font-size: 9px; line-height: 1.25; }
        .head-title { margin-top: 2px; font-size: 13px; font-weight: bold; text-transform: uppercase; }
        .head-office { margin-top: 2px; font-size: 14px; font-weight: bold; text-transform: uppercase; }
        .rule { border-bottom: 1px solid #111; margin-bottom: 10px; }
        h1 { margin: 0; color: #0b3f7c; font-size: 19px; }
        h2 { margin: 0 0 5px; color: #0b3f7c; font-size: 14px; }
        h3 { margin: 0 0 5px; color: #0b3f7c; font-size: 11px; }
        p { margin: 4px 0; }
        .muted { color: #64748b; }
        .period { margin-top: 3px; font-size: 10px; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 5px; margin: 7px -5px 9px; }
        .summary td { width: 50%; padding: 8px 9px; border: 1px solid #d8e2ef; background: #f7faff; }
        .summary strong { color: #0b3f7c; }
        .interpretation { margin: 9px 0; padding: 8px 10px; border: 1px solid #c9ddf5; background: #f2f7ff; }
        .interpretation ul { margin: 5px 0 0 16px; padding: 0; }
        .chart-heading { margin: 12px 0 6px; padding-bottom: 4px; border-bottom: 2px solid #1565c0; }
        .analytics-grid { width: 100%; margin: 6px 0 10px; }
        .analytics-card { width: 100%; margin-bottom: 10px; padding: 10px 11px; border: 1px solid #d8e2ef; page-break-inside: avoid; }
        .bar-row { margin: 6px 0; line-height: 14px; }
        .bar-label { display: inline-block; width: 31%; overflow: hidden; vertical-align: middle; white-space: nowrap; }
        .bar-track { display: inline-block; width: 57%; height: 13px; background: #e5edf7; vertical-align: middle; }
        .bar-fill { height: 13px; background: #2563eb; }
        .bar-value { display: inline-block; width: 10%; text-align: right; font-weight: bold; vertical-align: middle; }
        .bar-axis { margin: 3px 10% 0 31%; color: #64748b; font-size: 8px; }
        .bar-axis span { display: inline-block; width: 50%; }
        .bar-axis span:last-child { text-align: right; }
        .legend { margin-top: 6px; line-height: 16px; }
        .legend span { display: inline-block; margin-right: 8px; }
        .swatch { display: inline-block; width: 8px; height: 8px; margin-right: 3px; background: #2563eb; }
        .pie-wrap { text-align: center; margin: 5px 0 2px; }
        .pie { width: 126px; height: 126px; transform: rotate(-90deg); }
        .pie circle { fill: none; stroke-width: 8; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; page-break-inside: auto; }
        .data th, .data td { border: 1px solid #cbd5e1; padding: 5px 5px; text-align: left; }
        .data th { color: #fff; background: #1565c0; font-size: 9px; }
        .data td.num { text-align: right; }
        .section { margin-top: 12px; page-break-inside: avoid; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
<div class="sheet">
    <table class="letterhead">
        <tr>
            <td>
                @if(extension_loaded('gd'))
                    <img src="{{ public_path('images/intake-tagudin-seal-white.png') }}" class="logo" alt="Bayan ng Tagudin seal">
                    <img src="{{ public_path('images/intake-asap-seal-white.png') }}" class="logo asap" alt="ASAP Tagudin seal">
                @endif
            </td>
            <td>
                <div class="head-small">Republic of the Philippines<br>Province of Ilocos Sur</div>
                <div class="head-title">Municipality of Tagudin</div>
                <div class="head-office">Municipal Social Welfare and Development Office</div>
            </td>
            <td>
                @if(extension_loaded('gd'))
                    <img src="{{ public_path('images/intake-national-seal.png') }}" class="logo" alt="DSWD logo">
                @endif
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    <h1>CRISIMAP Reports &amp; Analytics</h1>
    <p class="period"><strong>Reporting period:</strong> {{ $periodLabel }} &nbsp; | &nbsp; <strong>Generated:</strong> {{ now(config('app.timezone', 'Asia/Manila'))->format('F d, Y h:i A') }}</p>

    <table class="summary">
        <tr>
            <td><strong>Total beneficiaries:</strong> {{ number_format($summary['total']) }}</td>
            <td><strong>Total amount distributed:</strong> PHP {{ number_format($summary['total_amount'], 2) }}</td>
        </tr>
    </table>

    <div class="interpretation">
        <h2>Report Interpretation</h2>
        <ul>
            @foreach($summary['interpretation'] as $statement)
                <li>{{ $statement }}</li>
            @endforeach
        </ul>
    </div>

    <h2 class="chart-heading">Report Graphs</h2>
    <div class="analytics-grid">
        <div class="analytics-card">
            <h2>AICS Beneficiaries by Barangay</h2>
            <p class="muted">Number of released beneficiaries in each barangay.</p>
            @forelse($barangayAnalytics as $barangay)
                @php($width = ((int) $barangay['count'] / $maxBarangayCount) * 100)
                <div class="bar-row"><span class="bar-label">{{ $barangay['name'] }}</span><span class="bar-track"><span class="bar-fill" style="width: {{ $width }}%;"></span></span><span class="bar-value">{{ $barangay['count'] }}</span></div>
            @empty
                <p class="muted">No barangay data for the selected criteria.</p>
            @endforelse
            @if($barangayAnalytics->isNotEmpty())
                <div class="bar-axis"><span>0 beneficiaries</span><span>{{ $maxBarangayCount }} beneficiaries</span></div>
            @endif
        </div>
        <div class="analytics-card">
            <h2>Assistance Distribution</h2>
            <p class="muted">Percentage of released beneficiaries by assistance type.</p>
            <div class="pie-wrap">
                <svg class="pie" viewBox="0 0 42 42" role="img" aria-label="Assistance distribution percentage chart">
                    <circle cx="21" cy="21" r="15.9155" stroke="#e5edf7"></circle>
                    @foreach($analytics as $item)
                        @php($dash = $item['share'])
                        @php($color = $item['color'] ?: '#2563eb')
                        <circle cx="21" cy="21" r="15.9155" stroke="{{ $color }}" stroke-dasharray="{{ $dash }} {{ $circumference - $dash }}" stroke-dashoffset="-{{ $offset }}"></circle>
                        @php($offset += $dash)
                    @endforeach
                </svg>
            </div>
            <div class="legend">
                @foreach($analytics as $item)
                    <span><i class="swatch" style="background: {{ $item['color'] ?: '#2563eb' }};"></i>{{ $item['name'] }} ({{ number_format($item['share'], 1) }}%)</span>
                @endforeach
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Assistance Analytics Detail</h2>
        <table class="data">
            <thead><tr><th>Rank</th><th>Assistance Type</th><th>Beneficiaries</th><th>Total Amount</th><th>Percentage</th></tr></thead>
            <tbody>
            @forelse($analytics as $rank => $item)
                <tr><td>{{ $rank + 1 }}</td><td>{{ $item['name'] }}</td><td class="num">{{ number_format($item['count']) }}</td><td class="num">PHP {{ number_format($item['amount'], 2) }}</td><td class="num">{{ number_format($item['share'], 1) }}%</td></tr>
            @empty
                <tr><td colspan="5">No assistance data for the selected criteria.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Barangay Analytics Detail</h2>
        <table class="data">
            <thead><tr><th>Rank</th><th>Barangay</th><th>Beneficiaries</th><th>Total Amount</th><th>Percentage</th></tr></thead>
            <tbody>
            @forelse($barangayAnalytics as $rank => $item)
                <tr><td>{{ $rank + 1 }}</td><td>{{ $item['name'] }}</td><td class="num">{{ number_format($item['count']) }}</td><td class="num">PHP {{ number_format($item['amount'], 2) }}</td><td class="num">{{ number_format($item['share'], 1) }}%</td></tr>
            @empty
                <tr><td colspan="5">No barangay data for the selected criteria.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="section page-break">
        <h2>Filtered Beneficiary Records</h2>
        <table class="data">
            <thead><tr><th>Reference</th><th>Client Name</th><th>Beneficiary Name</th><th>Barangay</th><th>Assistance Type</th><th>Applied</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($beneficiaries as $beneficiary)
                @php($client = $beneficiary->intake?->client_name ?: $beneficiary->caseRecord?->client_name ?: $beneficiary->name)
                @php($beneficiaryName = $beneficiary->intake?->beneficiary_name ?: $beneficiary->name)
                @php($beneficiaryDisplay = mb_strtolower(trim((string) $client)) === mb_strtolower(trim((string) $beneficiaryName)) ? 'Self' : $beneficiaryName)
                <tr>
                    <td>{{ $beneficiary->reference_code ?? 'N/A' }}</td><td>{{ $client }}</td><td>{{ $beneficiaryDisplay }}</td><td>{{ $beneficiary->barangay->name ?? '—' }}</td><td>{{ $beneficiary->assistanceType->name ?? '—' }}</td><td>{{ $beneficiary->date_of_assistance?->format('M d, Y') ?? '—' }}</td><td class="num">PHP {{ number_format((float) ($beneficiary->amount ?? 0), 2) }}</td><td>{{ $beneficiary->status }}</td>
                </tr>
            @empty
                <tr><td colspan="9">No records for the selected criteria.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
