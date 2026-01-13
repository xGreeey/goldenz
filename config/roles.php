<?php
/**
 * Role-Based Access Control Configuration
 */

return [
    'roles' => [
        'super_admin' => [
            'name' => 'Super Administrator',
            'description' => 'Full system access with all privileges',
            'permissions' => ['*'], // All permissions
            'redirect' => 'super-admin',
        ],
        'hr_admin' => [
            'name' => 'HR Administrator',
            'description' => 'Human Resources and Administration functions',
            'permissions' => [
                'employees.*',
                'hiring.*',
                'onboarding.*',
                'posts.*',
                'alerts.*',
                'dtr.view',
                'timeoff.*',
            ],
            'redirect' => 'hr-admin',
        ],
        'hr' => [
            'name' => 'HR Staff',
            'description' => 'HR staff member',
            'permissions' => [
                'employees.view',
                'employees.create',
                'employees.update',
                'hiring.view',
                'onboarding.view',
            ],
            'redirect' => 'hr-admin',
        ],
        'admin' => [
            'name' => 'Administrator',
            'description' => 'System administrator',
            'permissions' => [
                'employees.*',
                'settings.view',
            ],
            'redirect' => 'hr-admin',
        ],
        'operation' => [
            'name' => 'Operations',
            'description' => 'Field operations and deployment management',
            'permissions' => [
                'deployments.*',
                'dtr.*',
                'incidents.*',
                'overtime.*',
                'inventory.*',
            ],
            'redirect' => 'operation',
        ],
        'operations' => [
            'name' => 'Operations (Alias)',
            'description' => 'Operations staff',
            'permissions' => [
                'deployments.view',
                'dtr.view',
                'incidents.view',
            ],
            'redirect' => 'operation',
        ],
        'accounting' => [
            'name' => 'Accounting',
            'description' => 'Financial management and accounting',
            'permissions' => [
                'payroll.*',
                'expenses.*',
                'deductions.*',
                'loans.*',
                'reports.financial',
            ],
            'redirect' => 'accounting',
        ],
        'employee' => [
            'name' => 'Employee',
            'description' => 'Employee self-service portal',
            'permissions' => [
                'profile.view',
                'profile.update',
                'dtr.view_own',
                'timeoff.request',
                'payslips.view_own',
            ],
            'redirect' => 'employee',
        ],
        'developer' => [
            'name' => 'Developer',
            'description' => 'System developer access',
            'permissions' => ['*'],
            'redirect' => 'developer',
        ],
    ],
    
    'role_hierarchy' => [
        'super_admin' => ['*'],
        'hr_admin' => ['hr', 'admin'],
        'operation' => ['operations'],
    ],
];

