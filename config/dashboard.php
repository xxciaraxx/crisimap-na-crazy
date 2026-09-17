<?php

return [
    'navigation' => [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home', 'group' => 'overview', 'roles' => ['social_officer', 'focal_person', 'admin', 'super_admin']],
        ['label' => 'General Intake Sheets', 'route' => 'intakes.index', 'match' => 'intakes.*', 'icon' => 'intake', 'group' => 'overview', 'roles' => ['social_officer']],
        ['label' => 'Beneficiary Records', 'route' => 'beneficiary-records.index', 'match' => 'beneficiary-records.*', 'icon' => 'beneficiaries', 'group' => 'overview', 'roles' => ['social_officer']],
        ['label' => 'General Intake Sheets', 'route' => 'intakes.index', 'match' => 'intakes.*', 'icon' => 'intake', 'group' => 'overview', 'roles' => ['focal_person']],
        ['label' => 'Assistance Applications', 'route' => 'applications.index', 'match' => 'applications.*', 'icon' => 'applications', 'group' => 'overview', 'roles' => ['focal_person'], 'badge' => 'pending_applications'],
        ['label' => 'Beneficiary Records', 'route' => 'beneficiary-records.index', 'match' => 'beneficiary-records.*', 'icon' => 'beneficiaries', 'group' => 'overview', 'roles' => ['focal_person']],
        ['label' => 'General Intake Sheets', 'route' => 'intakes.index', 'match' => 'intakes.*', 'icon' => 'intake', 'group' => 'overview', 'roles' => ['admin', 'super_admin']],
        ['label' => 'Assistance Applications', 'route' => 'applications.index', 'match' => 'applications.*', 'icon' => 'applications', 'group' => 'overview', 'roles' => ['admin', 'super_admin'], 'badge' => 'pending_applications'],
        ['label' => 'Beneficiary Records', 'route' => 'beneficiary-records.index', 'match' => 'beneficiary-records.*', 'icon' => 'beneficiaries', 'group' => 'overview', 'roles' => ['admin', 'super_admin']],
        ['label' => 'GIS Map', 'route' => 'map', 'icon' => 'map', 'group' => 'overview', 'roles' => ['social_officer', 'focal_person', 'admin', 'super_admin']],
        ['label' => 'Reports', 'route' => 'reports.index', 'match' => 'reports.*', 'icon' => 'reports', 'group' => 'overview', 'roles' => ['social_officer', 'focal_person', 'admin', 'super_admin']],
        ['label' => 'Data Backup', 'route' => 'data-backup.index', 'match' => 'data-backup.*', 'icon' => 'backup', 'group' => 'data-backup', 'roles' => ['focal_person', 'admin', 'super_admin']],
        ['label' => 'Settings', 'route' => 'settings.index', 'match' => 'settings.*', 'icon' => 'settings', 'group' => 'administration', 'roles' => ['super_admin']],
        ['label' => 'User Management', 'route' => 'users.index', 'match' => 'users.*', 'icon' => 'user-management', 'group' => 'administration', 'roles' => ['super_admin'], 'badge' => 'pending_users'],
        ['label' => 'Activity Log', 'route' => 'activity-logs.index', 'match' => 'activity-logs.*', 'icon' => 'activity', 'group' => 'administration', 'roles' => ['super_admin']],
    ],
];
