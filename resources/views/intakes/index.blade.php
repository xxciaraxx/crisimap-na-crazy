<x-app-layout>
    <x-slot name="header">
        <div class="intakes-header">
            <div>
                <p class="intakes-eyebrow">Social Welfare Services</p>
                <h2>General Intake Sheets</h2>
                <p>Prepare, review, and track assistance intake records in one workspace.</p>
            </div>
            @can('create', App\Models\GeneralIntakeSheet::class)
                <a href="{{ route('intakes.create') }}" class="intakes-primary-action">
                    <span aria-hidden="true">+</span> New intake sheet
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="intakes-page">
        @if(session('success'))
            <div class="intakes-alert" role="status"><span aria-hidden="true">✓</span> {{ session('success') }}</div>
        @endif

        <div class="intakes-tabs" role="tablist" aria-label="Intake sheet status">
            <a class="{{ $tab === 'drafts' ? 'is-active' : '' }}" href="{{ route('intakes.index', ['tab' => 'drafts']) }}" role="tab" aria-selected="{{ $tab === 'drafts' ? 'true' : 'false' }}">
                <span class="intakes-tab-icon intakes-tab-icon-draft" aria-hidden="true">✎</span>
                <span><strong>Drafts</strong><small>Continue editing your sheets</small></span>
                <b>{{ $draftCount }}</b>
            </a>
            <a class="{{ $tab === 'submitted' ? 'is-active' : '' }}" href="{{ route('intakes.index', ['tab' => 'submitted']) }}" role="tab" aria-selected="{{ $tab === 'submitted' ? 'true' : 'false' }}">
                <span class="intakes-tab-icon intakes-tab-icon-submitted" aria-hidden="true">✓</span>
                <span><strong>Submitted</strong><small>Read-only records in review</small></span>
                <b>{{ $submittedCount }}</b>
            </a>
        </div>

        <section class="intakes-card">
            <div class="intakes-card-heading">
                <div>
                    <h3>{{ $tab === 'drafts' ? 'Draft General Intake Sheets' : 'Submitted General Intake Sheets' }}</h3>
                    <p>{{ $tab === 'drafts' ? 'Only drafts and returned sheets can be edited.' : 'Submitted sheets are locked for Social Worker Officers.' }}</p>
                </div>
                <span class="intakes-record-count">{{ $intakes->total() }} {{ Illuminate\Support\Str::plural('record', $intakes->total()) }}</span>
            </div>
            <form method="GET" action="{{ route('intakes.index') }}" class="intakes-filters">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <label class="intakes-search">
                    <span aria-hidden="true">⌕</span>
                    <input type="search" name="search" value="{{ $search }}" placeholder="Search applicant, intake no., program, or status" aria-label="Search {{ $tab }} intake sheets">
                </label>
                <label class="intakes-sort">
                    <span>Sort by</span>
                    <select name="sort" aria-label="Sort intake sheets">
                        <option value="date" @selected($sort === 'date')>Date</option>
                        <option value="name" @selected($sort === 'name')>Name</option>
                        <option value="status" @selected($sort === 'status')>Status</option>
                    </select>
                </label>
                <label class="intakes-sort">
                    <span>Order</span>
                    <select name="direction" aria-label="Sort direction">
                        @if($sort === 'date')
                            <option value="desc" @selected($direction === 'desc')>Newest first</option>
                            <option value="asc" @selected($direction === 'asc')>Oldest first</option>
                        @elseif($sort === 'name')
                            <option value="asc" @selected($direction === 'asc')>A–Z</option>
                            <option value="desc" @selected($direction === 'desc')>Z–A</option>
                        @else
                            <option value="asc" @selected($direction === 'asc')>A–Z</option>
                            <option value="desc" @selected($direction === 'desc')>Z–A</option>
                        @endif
                    </select>
                </label>
                <button type="submit" class="intakes-filter-action">Apply</button>
                @if($search !== '' || $sort !== 'date' || $direction !== 'desc')
                    <a href="{{ route('intakes.index', ['tab' => $tab]) }}" class="intakes-clear-action">Clear</a>
                @endif
            </form>

            <div class="intakes-table-wrap">
                <table class="intakes-table">
                    <thead><tr><th>Intake sheet</th><th>Applicant</th><th>Program</th><th>Status</th><th class="text-right">Action</th></tr></thead>
                    <tbody>
                        @forelse($intakes as $intake)
                            <tr>
                                <td><strong>{{ $intake->intake_number }}</strong><small>{{ optional($intake->intake_date)->format('M d, Y') }}</small></td>
                                <td>{{ $intake->client_name }}</td>
                                <td><span class="intakes-program">{{ $intake->program }}</span></td>
                                <td><span class="intakes-status intakes-status-{{ $intake->status }}">{{ $intake->statusLabel() }}</span></td>
                                <td class="text-right">
                                    <a class="intakes-open-action" href="{{ $tab === 'drafts' ? route('intakes.edit', $intake) : route('intakes.show', $intake) }}">{{ $tab === 'drafts' ? 'Open form' : 'View details' }} <span aria-hidden="true">→</span></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="intakes-empty"><span aria-hidden="true">○</span><strong>No {{ $tab }} yet</strong><small>{{ $tab === 'drafts' ? 'Create a new intake sheet to get started.' : 'Submitted sheets will appear here once they are sent for review.' }}</small></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($intakes->hasPages())<div class="intakes-pagination">{{ $intakes->links() }}</div>@endif
        </section>
    </div>

    <style>
        .intakes-page{min-height:calc(100vh - 150px);padding:2rem clamp(1rem,4vw,3.5rem);background:#f5f8fc}.intakes-header{display:flex;align-items:center;justify-content:space-between;gap:1.5rem}.intakes-header h2{margin:.2rem 0;color:#12345a;font-size:1.65rem;letter-spacing:-.035em}.intakes-header p:not(.intakes-eyebrow){margin:.35rem 0 0;color:#718198;font-size:.85rem}.intakes-eyebrow{margin:0;color:#2170b8;font-size:.68rem;font-weight:800;letter-spacing:.13em;text-transform:uppercase}.intakes-primary-action{display:inline-flex;align-items:center;gap:.45rem;border-radius:9px;padding:.72rem 1rem;color:#fff;background:#0e5cad;box-shadow:0 7px 16px #0e5cad2b;font-size:.78rem;font-weight:750;text-decoration:none}.intakes-primary-action span{font-size:1.2rem;font-weight:400;line-height:.7}.intakes-alert{display:flex;align-items:center;gap:.5rem;margin-bottom:1.2rem;border:1px solid #bce4ce;border-radius:9px;padding:.8rem 1rem;color:#17653b;background:#effbf4;font-size:.8rem}.intakes-alert span{font-weight:800}.intakes-tabs{display:flex;gap:.75rem;margin:1.8rem 0 1rem}.intakes-tabs a{display:flex;align-items:center;gap:.7rem;min-width:250px;border:1px solid #dbe6f2;border-radius:12px;padding:.85rem 1rem;color:#526982;background:#fff;text-decoration:none;transition:.15s ease}.intakes-tabs a:hover{border-color:#a9c8e7;transform:translateY(-1px)}.intakes-tabs a.is-active{border-color:#78aede;background:#f0f7ff;box-shadow:0 5px 14px #1c5b9414}.intakes-tab-icon{display:grid;place-items:center;width:30px;height:30px;border-radius:8px;font-size:.9rem}.intakes-tab-icon-draft{color:#90600a;background:#fff1cf}.intakes-tab-icon-submitted{color:#176d4a;background:#dff6e9}.intakes-tabs strong,.intakes-tabs small{display:block}.intakes-tabs strong{color:#1d385a;font-size:.82rem}.intakes-tabs small{margin-top:.15rem;color:#8291a3;font-size:.68rem}.intakes-tabs b{margin-left:auto;display:grid;place-items:center;min-width:25px;height:25px;border-radius:99px;color:#31577e;background:#e7f0f9;font-size:.7rem}.intakes-card{overflow:hidden;border:1px solid #dfE8f3;border-radius:14px;background:#fff;box-shadow:0 8px 22px #1d47740c}.intakes-card-heading{display:flex;align-items:center;justify-content:space-between;gap:1rem;border-bottom:1px solid #e9eff6;padding:1.25rem 1.4rem}.intakes-card-heading h3{margin:0;color:#1b395c;font-size:1rem}.intakes-card-heading p{margin:.3rem 0 0;color:#8291a3;font-size:.75rem}.intakes-record-count{color:#8291a3;font-size:.72rem}.intakes-filters{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;border-bottom:1px solid #e9eff6;padding:.9rem 1.4rem;background:#fbfdff}.intakes-search{display:flex;align-items:center;flex:1 1 280px;min-width:220px;border:1px solid #d4e1ed;border-radius:8px;background:#fff}.intakes-search span{padding-left:.7rem;color:#7290aa;font-size:1.2rem}.intakes-search input{width:100%;border:0;border-radius:8px;padding:.62rem .65rem;color:#294b69;background:transparent;font-size:.76rem;outline:0}.intakes-search:focus-within{border-color:#68a4d3;box-shadow:0 0 0 3px #68a4d31c}.intakes-sort{display:flex;align-items:center;gap:.35rem;color:#71859b;font-size:.7rem;font-weight:700}.intakes-sort select{border:1px solid #d4e1ed;border-radius:7px;padding:.62rem .5rem;color:#315775;background:#fff;font-size:.72rem;outline:0}.intakes-filter-action,.intakes-clear-action{border-radius:7px;padding:.64rem .8rem;font-size:.72rem;font-weight:750;text-decoration:none}.intakes-filter-action{border:0;color:#fff;background:#1768aa;cursor:pointer}.intakes-clear-action{border:1px solid #d4e1ed;color:#5c738b;background:#fff}.intakes-table{width:100%;border-collapse:collapse}.intakes-table th{padding:.75rem 1.4rem;color:#8696a8;background:#f9fbfd;font-size:.66rem;font-weight:800;letter-spacing:.08em;text-align:left;text-transform:uppercase}.intakes-table td{border-top:1px solid #edf2f7;padding:1rem 1.4rem;color:#405974;font-size:.78rem}.intakes-table td strong,.intakes-table td small{display:block}.intakes-table td strong{color:#244362;font-size:.78rem}.intakes-table td small{margin-top:.25rem;color:#9aa8b7;font-size:.68rem}.intakes-program{display:inline-block;border-radius:5px;padding:.25rem .45rem;color:#386287;background:#edf5fc;font-size:.67rem;font-weight:750}.intakes-status{display:inline-block;max-width:240px;border-radius:99px;padding:.3rem .55rem;color:#536980;background:#f1f4f7;font-size:.67rem;line-height:1.25}.intakes-status-draft{color:#8a5b0a;background:#fff4d9}.intakes-status-submitted,.intakes-status-under_review{color:#146b4a;background:#e5f7ed}.intakes-status-rejected{color:#a33c3c;background:#fff0f0}.intakes-open-action{color:#1467ac;font-size:.75rem;font-weight:750;text-decoration:none}.intakes-open-action span{margin-left:.25rem}.intakes-empty{text-align:center!important;padding:3.5rem 1rem!important}.intakes-empty span,.intakes-empty strong,.intakes-empty small{display:block}.intakes-empty span{color:#a5b5c6;font-size:1.5rem}.intakes-empty strong{margin-top:.4rem;color:#506a84;font-size:.85rem}.intakes-empty small{margin-top:.25rem;color:#91a0af;font-size:.72rem}.intakes-pagination{border-top:1px solid #edf2f7;padding:1rem 1.4rem}.intakes-pagination nav{display:flex;justify-content:center}@media(max-width:640px){.intakes-header{align-items:flex-start;flex-direction:column}.intakes-primary-action{width:100%;justify-content:center}.intakes-tabs{flex-direction:column}.intakes-tabs a{min-width:0}.intakes-table th,.intakes-table td{padding:.8rem .7rem}.intakes-table th:nth-child(3),.intakes-table td:nth-child(3){display:none}.intakes-card-heading{align-items:flex-start;flex-direction:column}.intakes-filters{align-items:stretch;flex-direction:column}.intakes-search{flex-basis:auto}.intakes-sort{justify-content:space-between}.intakes-sort select{flex:1}.intakes-filter-action,.intakes-clear-action{text-align:center}}
    </style>
</x-app-layout>
