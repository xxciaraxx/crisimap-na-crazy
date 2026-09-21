@php
    $user = Auth::user();
    $navigation = collect(config('dashboard.navigation'))->filter(fn ($item) => empty($item['roles']) || in_array($user->role, $item['roles']));
    $currentItem = $navigation->first(fn ($item) => request()->routeIs($item['match'] ?? $item['route']));
    $pageTitle = $currentItem['label'] ?? 'CRISIMAP';
    $navigationGroups = ['overview' => 'Overview', 'data-backup' => 'Data Backup', 'administration' => 'Administration'];
@endphp

<div x-cloak x-show="open" x-transition.opacity @click="open = false" class="app-sidebar-backdrop" aria-hidden="true"></div>
<aside class="app-sidebar" :class="{ 'is-open': open, 'is-collapsed': collapsed }" aria-label="Main navigation">
    <div class="app-sidebar-brand-row">
        <a class="app-sidebar-brand" href="{{ route('dashboard') }}" :data-tooltip="collapsed ? 'CRISIMAP' : null"><span class="app-brand-mark"><img src="{{ asset('images/crisimap-official-logo.png') }}" alt="CRISIMAP official logo"></span><span class="app-sidebar-copy"><strong>CRISI<span>MAP</span></strong><small>AICS PROGRAM</small></span></a>
        <button @click="collapsed = !collapsed" class="app-collapse-toggle" :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'" :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'">
            <span class="toggle-icon" :class="{ 'is-collapsed': collapsed }" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m15 5-7 7 7 7"/></svg></span>
        </button>
    </div>

    @foreach ($navigationGroups as $group => $groupLabel)
        @php($items = $navigation->where('group', $group))
        @if ($items->isNotEmpty())
            <section class="app-nav-group {{ $group === 'administration' ? 'app-admin-nav' : '' }}">
                <p class="app-nav-label app-sidebar-copy">{{ $groupLabel }}</p>
                <nav class="app-nav-links" aria-label="{{ $groupLabel }}">
                    @foreach ($items as $item)
                        @php($active = request()->routeIs($item['match'] ?? $item['route']))
                        <a href="{{ route($item['route'], $item['parameters'] ?? []) }}" @click="open = false" class="{{ $active ? 'is-active' : '' }}" :data-tooltip="collapsed ? '{{ $item['label'] }}' : null">
                            <span class="app-nav-icon" aria-hidden="true">
                                @switch($item['icon'])
                                    @case('home') <svg viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10H3zM9 20v-6h6v6"/></svg> @break
                                    @case('users') <svg viewBox="0 0 24 24"><path d="M16 20v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 20v-2a4 4 0 0 0-3-3.87M16 2.13a4 4 0 0 1 0 7.75"/></svg> @break
                                    @case('records') <svg viewBox="0 0 24 24"><path d="M5 3h11l3 3v15H5zM8 11h8M8 15h8M8 7h3"/></svg> @break
                                    @case('intake') <svg viewBox="0 0 24 24"><path d="M6 3h9l3 3v15H6zM15 3v4h4M9 12h6M9 16h6M9 8h2"/></svg> @break
                                    @case('applications') <svg viewBox="0 0 24 24"><path d="M5 4h14v16H5zM8 8h8M8 12h5M8 16h3"/><path d="m15 15 1.5 1.5L19 14"/></svg> @break
                                    @case('beneficiaries') <svg viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18" rx="2"/><circle cx="12" cy="9" r="2.5"/><path d="M8 17c.8-2 2.1-3 4-3s3.2 1 4 3"/></svg> @break
                                    @case('map') <svg viewBox="0 0 24 24"><path d="m9 18-6 3V6l6-3 6 3 6-3v15l-6 3zM9 3v15M15 6v15"/></svg> @break
                                    @case('reports') <svg viewBox="0 0 24 24"><path d="M5 3h14v18H5zM8 16v-4M12 16V8M16 16v-6"/></svg> @break
                                    @case('backup') <svg viewBox="0 0 24 24"><path d="M4 7.5h16v12H4zM7 7.5V5h10v2.5M8 12h8M8 15.5h5"/></svg> @break
                                    @case('user-management') <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3.5 20a5.5 5.5 0 0 1 11 0M16 11v7M12.5 14.5H19.5"/></svg> @break
                                    @case('activity') <svg viewBox="0 0 24 24"><path d="M4 13h4l2-7 4 12 2-5h4"/></svg> @break
                                    @case('settings') <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.12 2.12-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.56V20.3h-3v-.08A1.7 1.7 0 0 0 10.68 18.7a1.7 1.7 0 0 0-1.88.34l-.06.06-2.12-2.12.06-.06A1.7 1.7 0 0 0 7.02 15 1.7 1.7 0 0 0 5.46 14H5.4v-3h.06A1.7 1.7 0 0 0 7.02 10a1.7 1.7 0 0 0-.34-1.88l-.06-.06L8.74 5.94l.06.06a1.7 1.7 0 0 0 1.88.34 1.7 1.7 0 0 0 1.03-1.56V4.7h3v.08A1.7 1.7 0 0 0 15.74 6.3a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.12 2.12-.06.06A1.7 1.7 0 0 0 19.4 10c.12.57.62.98 1.2 1h.06v3h-.06c-.58.02-1.08.43-1.2 1Z"/></svg> @break
                                    @default <svg viewBox="0 0 24 24"><path d="M12 3a3 3 0 1 0 0 6 3 3 0 0 0 0-6ZM5 21a7 7 0 0 1 14 0"/></svg>
                                @endswitch
                            </span><span class="app-sidebar-copy">{{ $item['label'] }}</span>@if(!empty($item['badge']) && ($navigationBadges[$item['badge']] ?? 0))<span class="app-notification-badge">{{ $navigationBadges[$item['badge']] }}</span>@endif
                        </a>
                    @endforeach
                </nav>
            </section>
        @endif
    @endforeach

    <div class="app-sidebar-footer"><p class="app-nav-label app-sidebar-copy">Account</p><div class="app-profile-wrap">
        <button @click="sidebarProfileOpen = !sidebarProfileOpen; notificationsOpen = false; topProfileOpen = false" class="app-account-details" :data-tooltip="collapsed ? '{{ $user->name }}' : null" :aria-expanded="sidebarProfileOpen.toString()"><span class="app-account-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span><span class="app-sidebar-copy"><strong>{{ $user->name }}</strong><small>{{ \App\Models\User::roleLabel($user->role) }}</small></span><span class="app-profile-chevron app-sidebar-copy" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 5 7 7-7 7"/></svg></span></button>
        <div x-cloak x-show="sidebarProfileOpen" x-transition.origin.bottom.left @click.outside="sidebarProfileOpen = false" class="app-dropdown app-profile-dropdown"><a href="{{ route('profile.edit') }}">Profile</a>@if ($navigation->contains('route', 'settings.index')) <a href="{{ route('settings.index') }}">Settings</a> @endif<form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Log out</button></form></div>
    </div></div>
</aside>

@php($geotagNotification = $unreadNotifications->first(fn ($notification) => in_array($notification->data['type'] ?? null, ['geotag_success', 'geotag_failure'], true)))
@if($user->role === 'focal_person' && $geotagNotification)
    <div x-data="{ visible: true }" x-show="visible" x-transition class="app-geotag-popup {{ ($geotagNotification->data['type'] ?? null) === 'geotag_failure' ? 'is-failure' : '' }}" role="status">
        <button type="button" class="app-geotag-popup-close" @click="visible = false" aria-label="Dismiss notification">×</button>
        <strong>{{ $geotagNotification->data['title'] ?? 'Record successfully geotagged' }}</strong>
        <p>{{ $geotagNotification->data['message'] ?? '' }}</p>
        <form method="POST" action="{{ route('notifications.read', $geotagNotification->id) }}">
            @csrf
            <button type="submit">View record</button>
        </form>
    </div>
@endif

<header class="app-topbar" :class="{ 'is-collapsed': collapsed }"><div class="app-topbar-title"><button @click="open = !open" class="app-menu-toggle" :aria-expanded="open.toString()" aria-label="Toggle navigation"><span x-show="!open">☰</span><span x-show="open">×</span></button><span class="app-topbar-kicker">Workspace</span><strong>{{ $pageTitle }}</strong></div><div class="app-topbar-actions"><div x-data="{ now: new Date() }" x-init="setInterval(() => now = new Date(), 1000)" aria-label="Philippine time" style="display:flex;align-items:center;gap:12px;min-width:220px;padding:10px 16px;border:1px solid #b8d2f0;border-radius:12px;background:linear-gradient(135deg,#073d82 0%,#0b559f 60%,#176fc0 100%);box-shadow:0 6px 16px rgba(5,45,97,.22);color:#fff"><span aria-hidden="true" style="display:grid;place-items:center;width:34px;height:34px;border:1px solid rgba(255,255,255,.35);border-radius:9px;background:rgba(255,255,255,.16);font-size:22px;line-height:1">◷</span><span style="display:flex;flex:1;flex-direction:column;gap:4px;line-height:1"><small style="color:#bfdbfe;font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase">Philippine Time</small><strong style="color:#fff;font-size:15px;font-variant-numeric:tabular-nums;white-space:nowrap" x-text="now.toLocaleTimeString('en-PH', { timeZone: 'Asia/Manila', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true })"></strong></span><span aria-hidden="true" style="width:9px;height:9px;border:2px solid rgba(255,255,255,.45);border-radius:50%;background:#4ade80;box-shadow:0 0 0 3px rgba(74,222,128,.2)"></span></div><div class="app-notification-wrap"><button @click="notificationsOpen = !notificationsOpen; sidebarProfileOpen = false; topProfileOpen = false" class="app-icon-button" aria-label="Notifications{{ $unreadNotificationCount ? ', '.$unreadNotificationCount.' unread' : '' }}" :aria-expanded="notificationsOpen.toString()"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>@if($unreadNotificationCount)<span class="app-notification-badge">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>@endif</button><div x-cloak x-show="notificationsOpen" x-transition.origin.top.right @click.outside="notificationsOpen = false" class="app-dropdown app-notification-dropdown"><div class="app-notification-heading"><strong>Notifications</strong>@if($unreadNotificationCount)<form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button type="submit">Mark all read</button></form>@endif</div>@forelse($unreadNotifications as $notification)<form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="app-notification-item">@csrf<button type="submit"><span class="app-notification-type app-notification-type-{{ $notification->data['type'] ?? 'info' }}" aria-hidden="true">●</span><span><b>{{ $notification->data['title'] ?? 'Update' }}</b><span>{{ $notification->data['message'] ?? '' }}</span><small>{{ $notification->created_at->diffForHumans() }}</small></span></button></form>@empty<p>You're all caught up.</p>@endforelse@if($unreadNotificationCount > 8)<a class="app-notification-all" href="{{ route('notifications.index') }}">View all notifications</a>@endif</div></div><div class="app-profile-wrap"><button @click="topProfileOpen = !topProfileOpen; notificationsOpen = false; sidebarProfileOpen = false" class="app-top-profile" :aria-expanded="topProfileOpen.toString()"><span class="app-account-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span><span>{{ $user->name }}</span></button><div x-cloak x-show="topProfileOpen" x-transition.origin.top.right @click.outside="topProfileOpen = false" class="app-dropdown app-top-profile-dropdown"><a href="{{ route('profile.edit') }}">Profile</a>@if ($navigation->contains('route', 'settings.index')) <a href="{{ route('settings.index') }}">Settings</a> @endif<form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Log out</button></form></div></div></div></header>
