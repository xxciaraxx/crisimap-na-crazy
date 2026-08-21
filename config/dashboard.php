<?php

return [
    'navigation' => [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home', 'group' => 'overview', 'roles' => ['social_officer', 'focal_person', 'admin', 'super_admin']],
        ['label' => 'My Intake Sheets', 'route' => 'intakes.index', 'match' => 'intakes.*', 'icon' => 'records', 'group' => 'overview', 'roles' => ['social_officer']],
        ['label' => 'My Case Records', 'route' => 'case-records.index', 'match' => 'case-records.*', 'icon' => 'records', 'group' => 'overview', 'roles' => ['social_officer']],
        ['label' => 'Eligibility Reviews', 'route' => 'case-records.index', 'match' => 'case-records.*', 'icon' => 'records', 'group' => 'overview', 'roles' => ['focal_person']],
        ['label' => 'Applications & Release', 'route' => 'beneficiaries.index', 'match' => 'beneficiaries.*', 'icon' => 'users', 'group' => 'overview', 'roles' => ['focal_person']],
        ['label' => 'Applications', 'route' => 'beneficiaries.index', 'match' => 'beneficiaries.*', 'icon' => 'users', 'group' => 'overview', 'roles' => ['admin', 'super_admin']],
        ['label' => 'Case Records', 'route' => 'case-records.index', 'match' => 'case-records.*', 'icon' => 'records', 'group' => 'overview', 'roles' => ['admin', 'super_admin']],
        ['label' => 'Applications awaiting approval', 'route' => 'beneficiaries.index', 'parameters' => ['status' => 'Pending'], 'match' => 'beneficiaries.*', 'icon' => 'records', 'group' => 'overview', 'roles' => ['admin'], 'badge' => 'pending_applications'],
        ['label' => 'GIS Map', 'route' => 'map', 'icon' => 'map', 'group' => 'overview', 'roles' => ['social_officer', 'focal_person', 'admin', 'super_admin']],
        ['label' => 'Reports', 'route' => 'reports.index', 'match' => 'reports.*', 'icon' => 'reports', 'group' => 'overview', 'roles' => ['focal_person', 'admin', 'super_admin']],
        ['label' => 'Settings', 'route' => 'settings.index', 'match' => 'settings.*', 'icon' => 'settings', 'group' => 'administration', 'roles' => ['super_admin']],
        ['label' => 'User Management', 'route' => 'users.index', 'match' => 'users.*', 'icon' => 'users', 'group' => 'administration', 'roles' => ['super_admin'], 'badge' => 'pending_users'],
        ['label' => 'Activity Log', 'route' => 'activity-logs.index', 'match' => 'activity-logs.*', 'icon' => 'records', 'group' => 'administration', 'roles' => ['super_admin']],
    ],
];
