<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/css/dashboard.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <div class="app-shell min-h-screen bg-gray-100 {{ request()->routeIs('map') && request()->boolean('full') ? 'is-full-map-shell' : '' }}" x-data="{ open: false, collapsed: false, sidebarProfileOpen: false, topProfileOpen: false, notificationsOpen: false, init() { const saved = localStorage.getItem('crisimap.sidebarCollapsed'); this.collapsed = saved === null ? {{ request()->routeIs('map') ? 'true' : 'false' }} : saved === 'true'; this.$watch('collapsed', value => localStorage.setItem('crisimap.sidebarCollapsed', value)); }, closeMenus() { this.sidebarProfileOpen = false; this.topProfileOpen = false; this.notificationsOpen = false; } }" :class="{ 'sidebar-collapsed': collapsed }" @keydown.escape.window="open = false; closeMenus()">
            @include('layouts.navigation')

            <div class="app-page-area">
                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white shadow {{ request()->routeIs('map') && request()->boolean('full') ? 'is-full-map-heading' : '' }}">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
        <div id="session-expired-modal" class="session-expired-modal" hidden role="dialog" aria-modal="true" aria-labelledby="session-expired-title">
            <div class="session-expired-card">
                <div class="session-expired-icon" aria-hidden="true">!</div>
                <p class="session-expired-eyebrow">Security notice</p>
                <h2 id="session-expired-title">Your session has expired</h2>
                <p>You have been logged out because there was no activity for 15 minutes. Please log in again to continue.</p>
                <a href="{{ route('login') }}" class="session-expired-button">Log in again</a>
            </div>
        </div>
        <style>
            .is-full-map-shell .app-sidebar,.is-full-map-shell .app-topbar,.is-full-map-shell .is-full-map-heading{display:none}.is-full-map-shell .app-page-area{margin-left:0;padding-top:0}.is-full-map-shell main{min-height:100vh}
            .session-expired-modal{position:fixed;z-index:200;inset:0;display:grid;place-items:center;padding:1rem;background:rgba(15,38,65,.72)}.session-expired-modal[hidden]{display:none}.session-expired-card{width:min(100%,410px);border:1px solid #dce7f2;border-radius:16px;padding:2rem;background:#fff;box-shadow:0 24px 60px rgba(8,35,65,.28);text-align:center}.session-expired-icon{display:grid;place-items:center;width:44px;height:44px;margin:0 auto 1rem;border-radius:50%;color:#fff;background:#d28a16;font-size:1.35rem;font-weight:800}.session-expired-eyebrow{margin:0;color:#2170b8;font-size:.67rem;font-weight:800;letter-spacing:.13em;text-transform:uppercase}.session-expired-card h2{margin:.35rem 0;color:#173b60;font-size:1.25rem;letter-spacing:-.02em}.session-expired-card>p:not(.session-expired-eyebrow){margin:.6rem auto 1.25rem;color:#718198;font-size:.82rem;line-height:1.5}.session-expired-button{display:inline-flex;justify-content:center;width:100%;border-radius:8px;padding:.75rem 1rem;color:#fff;background:#1768aa;font-size:.8rem;font-weight:750;text-decoration:none}.session-expired-button:hover{background:#12568d}
        </style>
        <script>
            (() => {
                const inactivityLimit = 15 * 60 * 1000;
                const modal = document.getElementById('session-expired-modal');
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                let timer;
                let expired = false;

                const expireSession = async () => {
                    if (expired) return;
                    expired = true;
                    clearTimeout(timer);
                    try {
                        await fetch(@json(route('logout')), {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken || '',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                    } finally {
                        if (modal) {
                            modal.hidden = false;
                            modal.querySelector('.session-expired-button')?.focus();
                        }
                    }
                };

                const resetTimer = () => {
                    if (expired) return;
                    clearTimeout(timer);
                    timer = setTimeout(expireSession, inactivityLimit);
                };

                ['click', 'keydown', 'mousemove', 'scroll', 'touchstart'].forEach((eventName) => {
                    document.addEventListener(eventName, resetTimer, { passive: true });
                });
                resetTimer();
            })();
        </script>
        @livewireScripts
    </body>
</html>
