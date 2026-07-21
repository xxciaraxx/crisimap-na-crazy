<?php

return [
    'navigation' => [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Beneficiaries', 'route' => 'beneficiaries.index', 'match' => 'beneficiaries.*', 'icon' => 'users', 'roles' => ['focal_person', 'admin', 'super_admin']],
        ['label' => 'Case Records', 'route' => 'case-records.index', 'match' => 'case-records.*', 'icon' => 'records'],
        ['label' => 'GIS Map', 'route' => 'map', 'icon' => 'map'],
        ['label' => 'Reports', 'route' => 'reports.index', 'match' => 'reports.*', 'icon' => 'reports'],
        ['label' => 'Settings', 'route' => 'settings.index', 'match' => 'settings.*', 'icon' => 'settings', 'roles' => ['super_admin']],
        ['label' => 'User Management', 'route' => 'users.index', 'match' => 'users.*', 'icon' => 'users', 'roles' => ['super_admin']],
    ],
];
