@php
    $user = Auth::user();
    $navigation = collect(config('dashboard.navigation'))->filter(fn ($item) => empty($item['roles']) || in_array($user->role, $item['roles']));
    $currentItem = $navigation->first(fn ($item) => request()->routeIs($item['match'] ?? $item['route']));
    $pageTitle = $currentItem['label'] ?? 'CRISIMAP';
    $navigationGroups = ['overview' => 'Overview', 'administration' => 'Administration'];
@endphp

<div x-cloak x-show="open" x-transition.opacity @click="open = false" class="app-sidebar-backdrop" aria-hidden="true"></div>
<aside class="app-sidebar" :class="{ 'is-open': open, 'is-collapsed': collapsed }" aria-label="Main navigation">
    <div class="app-sidebar-brand-row">
        <a class="app-sidebar-brand" href="{{ route('dashboard') }}" :data-tooltip="collapsed ? 'CRISIMAP' : null"><span class="app-brand-mark" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 21s7-6.1 7-12A7 7 0 1 0 5 9c0 5.9 7 12 7 12Z"/><circle cx="12" cy="9" r="2.3"/></svg></span><span class="app-sidebar-copy"><strong>CRISI<span>MAP</span></strong><small>AICS PROGRAM</small></span></a>
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
                        <a href="{{ route($item['route']) }}" @click="open = false" class="{{ $active ? 'is-active' : '' }}" :data-tooltip="collapsed ? '{{ $item['label'] }}' : null">
                            <span class="app-nav-icon" aria-hidden="true">
                                @switch($item['icon'])
                                    @case('home') <svg viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10H3zM9 20v-6h6v6"/></svg> @break
                                    @case('users') <svg viewBox="0 0 24 24"><path d="M16 20v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 20v-2a4 4 0 0 0-3-3.87M16 2.13a4 4 0 0 1 0 7.75"/></svg> @break
                                    @case('records') <svg viewBox="0 0 24 24"><path d="M5 3h11l3 3v15H5zM8 11h8M8 15h8M8 7h3"/></svg> @break
                                    @case('map') <svg viewBox="0 0 24 24"><path d="m9 18-6 3V6l6-3 6 3 6-3v15l-6 3zM9 3v15M15 6v15"/></svg> @break
                                    @case('reports') <svg viewBox="0 0 24 24"><path d="M5 3h14v18H5zM8 16v-4M12 16V8M16 16v-6"/></svg> @break
                                    @case('settings') <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.12 2.12-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.56V20.3h-3v-.08A1.7 1.7 0 0 0 10.68 18.7a1.7 1.7 0 0 0-1.88.34l-.06.06-2.12-2.12.06-.06A1.7 1.7 0 0 0 7.02 15 1.7 1.7 0 0 0 5.46 14H5.4v-3h.06A1.7 1.7 0 0 0 7.02 10a1.7 1.7 0 0 0-.34-1.88l-.06-.06L8.74 5.94l.06.06a1.7 1.7 0 0 0 1.88.34 1.7 1.7 0 0 0 1.03-1.56V4.7h3v.08A1.7 1.7 0 0 0 15.74 6.3a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.12 2.12-.06.06A1.7 1.7 0 0 0 19.4 10c.12.57.62.98 1.2 1h.06v3h-.06c-.58.02-1.08.43-1.2 1Z"/></svg> @break
                                    @default <svg viewBox="0 0 24 24"><path d="M12 3a3 3 0 1 0 0 6 3 3 0 0 0 0-6ZM5 21a7 7 0 0 1 14 0"/></svg>
                                @endswitch
                            </span><span class="app-sidebar-copy">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </section>
        @endif
    @endforeach

    <div class="app-sidebar-footer"><p class="app-nav-label app-sidebar-copy">Account</p><div class="app-profile-wrap">
        <button @click="sidebarProfileOpen = !sidebarProfileOpen; notificationsOpen = false; topProfileOpen = false" class="app-account-details" :data-tooltip="collapsed ? '{{ $user->name }}' : null" :aria-expanded="sidebarProfileOpen.toString()"><span class="app-account-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span><span class="app-sidebar-copy"><strong>{{ $user->name }}</strong><small>{{ ucwords(str_replace('_', ' ', $user->role)) }}</small></span><span class="app-profile-chevron app-sidebar-copy" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 5 7 7-7 7"/></svg></span></button>
        <div x-cloak x-show="sidebarProfileOpen" x-transition.origin.bottom.left @click.outside="sidebarProfileOpen = false" class="app-dropdown app-profile-dropdown"><a href="{{ route('profile.edit') }}">Profile</a>@if ($navigation->contains('route', 'settings.index')) <a href="{{ route('settings.index') }}">Settings</a> @endif<form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Log out</button></form></div>
    </div></div>
</aside>

<header class="app-topbar" :class="{ 'is-collapsed': collapsed }"><div class="app-topbar-title"><button @click="open = !open" class="app-menu-toggle" :aria-expanded="open.toString()" aria-label="Toggle navigation"><span x-show="!open">☰</span><span x-show="open">×</span></button><span class="app-topbar-kicker">Workspace</span><strong>{{ $pageTitle }}</strong></div><div class="app-topbar-actions"><div class="app-notification-wrap"><button @click="notificationsOpen = !notificationsOpen; sidebarProfileOpen = false; topProfileOpen = false" class="app-icon-button" aria-label="Notifications" :aria-expanded="notificationsOpen.toString()">♧@if($unreadNotifications->isNotEmpty())<span class="app-notification-badge">{{ $unreadNotifications->count() }}</span>@endif</button><div x-cloak x-show="notificationsOpen" x-transition.origin.top.right @click.outside="notificationsOpen = false" class="app-dropdown app-notification-dropdown"><strong>Notifications</strong>@forelse($unreadNotifications as $notification)<a class="block border-t py-2 text-sm" href="{{ $notification->data['url'] ?? '#' }}"><b>{{ $notification->data['title'] ?? 'Update' }}</b><span class="block text-xs">{{ $notification->data['message'] ?? '' }}</span></a>@empty<p>You're all caught up.</p>@endforelse</div></div><div class="app-profile-wrap"><button @click="topProfileOpen = !topProfileOpen; notificationsOpen = false; sidebarProfileOpen = false" class="app-top-profile" :aria-expanded="topProfileOpen.toString()"><span class="app-account-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span><span>{{ $user->name }}</span></button><div x-cloak x-show="topProfileOpen" x-transition.origin.top.right @click.outside="topProfileOpen = false" class="app-dropdown app-top-profile-dropdown"><a href="{{ route('profile.edit') }}">Profile</a>@if ($navigation->contains('route', 'settings.index')) <a href="{{ route('settings.index') }}">Settings</a> @endif<form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Log out</button></form></div></div></div></header>
