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
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <div class="app-shell min-h-screen bg-gray-100" x-data="{ open: false, collapsed: false, sidebarProfileOpen: false, topProfileOpen: false, notificationsOpen: false, init() { const saved = localStorage.getItem('crisimap.sidebarCollapsed'); this.collapsed = saved === null ? {{ request()->routeIs('map') ? 'true' : 'false' }} : saved === 'true'; this.$watch('collapsed', value => localStorage.setItem('crisimap.sidebarCollapsed', value)); }, closeMenus() { this.sidebarProfileOpen = false; this.topProfileOpen = false; this.notificationsOpen = false; } }" :class="{ 'sidebar-collapsed': collapsed }" @keydown.escape.window="open = false; closeMenus()">
            @include('layouts.navigation')

            <div class="app-page-area">
                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white shadow">
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
        @livewireScripts
    </body>
</html>
