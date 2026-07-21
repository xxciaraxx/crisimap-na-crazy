@php
    $user = Auth::user();
    $navigation = collect(config('dashboard.navigation'))->filter(fn ($item) => empty($item['roles']) || in_array($user->role, $item['roles']));
    $currentItem = $navigation->first(fn ($item) => request()->routeIs($item['match'] ?? $item['route']));
    $pageTitle = $currentItem['label'] ?? 'CRISIMAP';
@endphp

<div x-cloak x-show="open" x-transition.opacity @click="open = false" class="app-sidebar-backdrop" aria-hidden="true"></div>

    <aside class="app-sidebar" :class="{ 'is-open': open, 'is-collapsed': collapsed }" aria-label="Main navigation">
        <a class="app-sidebar-brand" href="{{ route('dashboard') }}" :title="collapsed ? 'CRISIMAP' : ''">
            <span class="app-brand-mark">C</span>
            <span class="app-sidebar-copy"><strong>CRISI<span>MAP</span></strong><small>AICS PROGRAM</small></span>
        </a>

        <p class="app-nav-label app-sidebar-copy">Workspace</p>
        <nav class="app-nav-links">
            @foreach ($navigation->take(5) as $item)
                @php($active = request()->routeIs($item['match'] ?? $item['route']))
                <a href="{{ route($item['route']) }}" @click="open = false" class="{{ $active ? 'is-active' : '' }}" :title="collapsed ? '{{ $item['label'] }}' : ''">
                    <span class="app-nav-icon" aria-hidden="true">
                        @if($item['icon'] === 'home') ⌂ @elseif($item['icon'] === 'users') ♙ @elseif($item['icon'] === 'records') ▣ @elseif($item['icon'] === 'map') ⌖ @elseif($item['icon'] === 'reports') ▤ @else ⚙ @endif
                    </span><span class="app-sidebar-copy">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        @if ($navigation->count() > 5)
            <div class="app-admin-nav">
                <p class="app-nav-label app-sidebar-copy">Administration</p>
                <nav class="app-nav-links">
                    @foreach ($navigation->skip(5) as $item)
                        @php($active = request()->routeIs($item['match'] ?? $item['route']))
                        <a href="{{ route($item['route']) }}" @click="open = false" class="{{ $active ? 'is-active' : '' }}" :title="collapsed ? '{{ $item['label'] }}' : ''"><span class="app-nav-icon" aria-hidden="true">{{ $item['icon'] === 'settings' ? '⚙' : '♙' }}</span><span class="app-sidebar-copy">{{ $item['label'] }}</span></a>
                    @endforeach
                </nav>
            </div>
        @endif

        <div class="app-sidebar-footer">
            <div class="app-profile-wrap">
                <button @click="sidebarProfileOpen = !sidebarProfileOpen; notificationsOpen = false; topProfileOpen = false" class="app-account-details" :title="collapsed ? '{{ $user->name }}' : ''" :aria-expanded="sidebarProfileOpen.toString()">
                    <span class="app-account-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    <span class="app-sidebar-copy"><strong>{{ $user->name }}</strong><small>{{ ucwords(str_replace('_', ' ', $user->role)) }}</small></span>
                </button>
                <div x-cloak x-show="sidebarProfileOpen" x-transition.origin.bottom.left @click.outside="sidebarProfileOpen = false" class="app-dropdown app-profile-dropdown">
                    <a href="{{ route('profile.edit') }}">Profile</a>
                    @if ($navigation->contains('route', 'settings.index')) <a href="{{ route('settings.index') }}">Settings</a> @endif
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Log out</button></form>
                </div>
            </div>
            <button @click="collapsed = !collapsed" class="app-collapse-toggle" :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'" :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"><span x-text="collapsed ? '›' : '‹'"></span><span class="app-sidebar-copy">Collapse</span></button>
        </div>
    </aside>

    <header class="app-topbar" :class="{ 'is-collapsed': collapsed }">
        <div class="app-topbar-title"><button @click="open = !open" class="app-menu-toggle" :aria-expanded="open.toString()" aria-label="Toggle navigation"><span x-show="!open">☰</span><span x-show="open">×</span></button><span class="app-topbar-kicker">Workspace</span><strong>{{ $pageTitle }}</strong></div>
        <div class="app-topbar-actions">
            <div class="app-notification-wrap">
            <button @click="notificationsOpen = !notificationsOpen; sidebarProfileOpen = false; topProfileOpen = false" class="app-icon-button" aria-label="Notifications" :aria-expanded="notificationsOpen.toString()">♧<span class="app-notification-badge">0</span></button>
                <div x-cloak x-show="notificationsOpen" x-transition.origin.top.right @click.outside="notificationsOpen = false" class="app-dropdown app-notification-dropdown"><strong>Notifications</strong><p>You’re all caught up.</p></div>
            </div>
            <div class="app-profile-wrap"><button @click="topProfileOpen = !topProfileOpen; notificationsOpen = false; sidebarProfileOpen = false" class="app-top-profile" :aria-expanded="topProfileOpen.toString()"><span class="app-account-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span><span>{{ $user->name }}</span></button><div x-cloak x-show="topProfileOpen" x-transition.origin.top.right @click.outside="topProfileOpen = false" class="app-dropdown app-top-profile-dropdown"><a href="{{ route('profile.edit') }}">Profile</a>@if ($navigation->contains('route', 'settings.index')) <a href="{{ route('settings.index') }}">Settings</a> @endif<form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Log out</button></form></div></div>
        </div>
    </header>
