<?php

return [
    'navigation' => [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home', 'group' => 'overview'],
        ['label' => 'Beneficiaries', 'route' => 'beneficiaries.index', 'match' => 'beneficiaries.*', 'icon' => 'users', 'group' => 'overview', 'roles' => ['focal_person', 'admin', 'super_admin']],
        ['label' => 'Case Records', 'route' => 'case-records.index', 'match' => 'case-records.*', 'icon' => 'records', 'group' => 'overview'],
        ['label' => 'GIS Map', 'route' => 'map', 'icon' => 'map', 'group' => 'overview'],
        ['label' => 'Reports', 'route' => 'reports.index', 'match' => 'reports.*', 'icon' => 'reports', 'group' => 'overview'],
        ['label' => 'Settings', 'route' => 'settings.index', 'match' => 'settings.*', 'icon' => 'settings', 'group' => 'administration', 'roles' => ['super_admin']],
        ['label' => 'User Management', 'route' => 'users.index', 'match' => 'users.*', 'icon' => 'users', 'group' => 'administration', 'roles' => ['super_admin']],
    ],
];
