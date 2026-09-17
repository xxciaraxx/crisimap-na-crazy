<x-app-layout>
    <div class="admin-dashboard">
        <div class="admin-dashboard__container">
            <header class="admin-dashboard__intro">
                <div><p>System administration</p><h1>Operational overview</h1><span>Review account requests, access health, and system reference data.</span></div>
                <a href="{{ route('users.index', ['status' => 'pending']) }}" class="admin-dashboard__button">Review user management</a>
            </header>

            <section class="admin-dashboard__grid admin-dashboard__grid--primary">
                <article class="admin-card admin-card--beneficiaries"><p class="admin-card__eyebrow">Beneficiary records</p><h2>Total beneficiaries</h2><strong class="admin-card__beneficiary-total">{{ number_format($beneficiarySummary['total_records']) }}</strong><div class="admin-card__beneficiary-details"><span><b>{{ number_format($beneficiarySummary['single_record_beneficiaries']) }}</b> Single-record</span><span><b>{{ number_format($beneficiarySummary['returning_records']) }}</b> Returning records</span></div></article>
                <article class="admin-card admin-card--approvals">
                    <div class="admin-card__heading"><div><p class="admin-card__eyebrow">Requires attention</p><h2>Pending approvals</h2></div><b>{{ $pendingApprovals->count() }}</b></div>
                    <div class="admin-approval-list">
                        @forelse($pendingApprovals as $user)
                            <div class="admin-approval" x-data="{ rejectOpen: false }">
                                <div><strong>{{ $user->name }}</strong><span>{{ \App\Models\User::roleLabel($user->role) }} · {{ $user->created_at->diffForHumans() }}</span><small>{{ $user->email }}</small></div>
                                <div class="admin-approval__actions"><form method="POST" action="{{ route('users.approve', $user) }}">@csrf<button type="submit">Approve</button></form><button type="button" class="is-reject" @click="rejectOpen = true">Reject</button></div>
                                <div x-cloak x-show="rejectOpen" class="admin-reject-dialog" @keydown.escape.window="rejectOpen = false"><div @click.outside="rejectOpen = false"><h3>Reject account request</h3><p>Provide a reason that the applicant can understand.</p><form method="POST" action="{{ route('users.reject', $user) }}">@csrf<textarea name="rejection_reason" required maxlength="2000" placeholder="Reason for rejection"></textarea><div><button type="button" class="is-cancel" @click="rejectOpen = false">Cancel</button><button type="submit" class="is-reject">Reject request</button></div></form></div></div>
                            </div>
                        @empty
                            <p class="admin-empty">No accounts awaiting approval.</p>
                        @endforelse
                    </div>
                    <a class="admin-card__link" href="{{ route('users.index', ['status' => 'pending']) }}">View all in User Management <span>→</span></a>
                </article>

                <div class="admin-dashboard__stack">
                    <article class="admin-card"><p class="admin-card__eyebrow">Role roster snapshot</p><h2>Account coverage</h2><dl class="admin-roster"><div><dt>MSWDO Head</dt><dd>@if($roleRoster['admin'])<a href="{{ route('users.edit', $roleRoster['admin']) }}">{{ $roleRoster['admin']->name }}</a>@else Vacant @endif</dd></div><div><dt>Focal Person</dt><dd>@if($roleRoster['focal_person'])<a href="{{ route('users.edit', $roleRoster['focal_person']) }}">{{ $roleRoster['focal_person']->name }}</a>@else Vacant @endif</dd></div><div><dt>Social Officers</dt><dd>{{ $roleRoster['social_officer_count'] }} active</dd></div></dl><a class="admin-card__link" href="{{ route('users.index') }}">Manage accounts <span>→</span></a></article>
                    <article class="admin-card"><p class="admin-card__eyebrow">System health</p><h2>Access signals</h2><div class="admin-stat-pair"><div><strong>{{ $failedLoginsToday }}</strong><span>Failed logins today</span></div><div><strong>{{ $incompleteOnboarding }}</strong><span>Incomplete onboarding</span></div></div></article>
                </div>
            </section>

            <section class="admin-dashboard__grid admin-dashboard__grid--secondary">
                <article class="admin-card"><div class="admin-card__heading"><div><p class="admin-card__eyebrow">Audit preview</p><h2>Recent activity</h2></div></div><div class="admin-activity-list">@forelse($recentActivity as $log)<div><span>{{ $log->user?->name ?? 'System' }}</span><p>{{ $log->description }}</p><time>{{ $log->created_at->diffForHumans() }}</time></div>@empty<p class="admin-empty">No activity recorded yet.</p>@endforelse</div><a class="admin-card__link" href="{{ route('activity-logs.index') }}">View full activity log <span>→</span></a></article>
                <div class="admin-dashboard__stack"><a class="admin-card admin-card--clickable" href="{{ route('settings.index') }}"><p class="admin-card__eyebrow">Reference data</p><h2>Configuration</h2><div class="admin-stat-pair"><div><strong>{{ $referenceData['barangays'] }}</strong><span>Barangays</span></div><div><strong>{{ $referenceData['assistance_types'] }}</strong><span>Assistance types</span></div></div><span class="admin-card__link">Open settings <span>→</span></span></a><article class="admin-card"><p class="admin-card__eyebrow">Geocoding pipeline</p><h2>Location health</h2><div class="admin-stat-pair"><div><strong>{{ $geocodingHealth['unresolved'] }}</strong><span>Unresolved records</span></div><div><strong>{{ $geocodingHealth['failed'] }}</strong><span>Failed attempts</span></div></div></article></div>
            </section>
        </div>
    </div>
</x-app-layout>
