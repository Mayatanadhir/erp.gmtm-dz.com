<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Standard System Roles Registry
    |--------------------------------------------------------------------------
    |
    | Static role definitions, functional titles, scopes, and system protection.
    | Managed statically in code rather than arbitrary runtime records.
    |
    */
    'roles' => [
        'Super-Admin' => [
            'title' => 'Super Administrator (Full Sovereign Access)',
            'scope' => 'Full sovereign authority over system configurations, forensic tables, and all application data.',
            'is_super' => true,
            'is_default' => false,
        ],
        'Admin' => [
            'title' => 'System Administrator (Operations & Users)',
            'scope' => 'Operational and user management with access to standard business tables, excluding security-critical tables.',
            'is_super' => false,
            'is_default' => false,
        ],
        'User' => [
            'title' => 'Standard User (General Workspace Services)',
            'scope' => 'Access to general application tools and profile workspace without administrative privileges.',
            'is_super' => false,
            'is_default' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Standard System Business Modules (Categories)
    |--------------------------------------------------------------------------
    |
    | High-level business modules grouping fine-grained entity permissions.
    |
    */
    'modules' => [
        'system' => [
            'name' => 'System Security & Administration',
            'icon' => 'fa-shield-alt',
            'color' => 'rose',
        ],
        'metrology' => [
            'name' => 'Metrology & Equipments',
            'icon' => 'fa-microscope',
            'color' => 'indigo',
            'view_permission' => 'view metrology',
            'perms' => ['view metrology'],
        ],
        'operations' => [
            'name' => 'Operations & Projects',
            'icon' => 'fa-briefcase',
            'color' => 'amber',
            'view_permission' => 'view operations',
            'perms' => ['view operations'],
        ],
        'financial' => [
            'name' => 'Financial Management',
            'icon' => 'fa-coins',
            'color' => 'teal',
            'view_permission' => 'view financial',
            'perms' => ['view financial'],
        ],
        'analytics' => [
            'name' => 'Internal and Analytical Management',
            'icon' => 'fa-chart-line',
            'color' => 'blue',
            'view_permission' => 'view analytics',
            'perms' => ['view analytics'],
        ],
        'master_data' => [
            'name' => 'Master Data / Reference Data',
            'icon' => 'fa-database',
            'color' => 'emerald',
            'view_permission' => 'view master data',
            'perms' => ['view master data'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Standard System Permission Groups
    |--------------------------------------------------------------------------
    |
    | Static permission catalog defining modules, actions, and UI metadata.
    | Managed statically in code rather than by dynamic schema inspection.
    |
    */
    'groups' => [
        'users_management' => [
            'module' => 'system',
            'icon' => 'fa-users',
            'lang' => 'Users',
            'perms' => [
                'view users',
                'create users',
                'edit users',
                'delete users',
            ],
        ],
        'roles_management' => [
            'module' => 'system',
            'icon' => 'fa-user-tag',
            'lang' => 'Roles',
            'perms' => [
                'view roles',
                'create roles',
                'edit roles',
                'delete roles',
            ],
        ],
        'permissions_management' => [
            'module' => 'system',
            'icon' => 'fa-shield-alt',
            'lang' => 'Permissions',
            'perms' => [
                'view permissions',
                'create permissions',
                'edit permissions',
                'delete permissions',
            ],
        ],

        // 🔬 Metrology & Equipments
        'measuring_instruments' => [
            'module' => 'metrology',
            'entity' => 'measuring instruments',
            'icon' => 'fa-ruler-combined',
            'lang' => 'Measuring Instruments',
            'perms' => [
                'view measuring instruments',
                'create measuring instruments',
                'edit measuring instruments',
                'delete measuring instruments',
            ],
        ],
        'equipment' => [
            'module' => 'metrology',
            'entity' => 'equipment',
            'icon' => 'fa-tools',
            'lang' => 'Equipment',
            'perms' => [
                'view equipment',
                'create equipment',
                'edit equipment',
                'delete equipment',
            ],
        ],
        'calibrator_movements' => [
            'module' => 'metrology',
            'entity' => 'calibrator movements',
            'icon' => 'fa-exchange-alt',
            'lang' => 'Calibrator Movements',
            'perms' => [
                'view calibrator movements',
                'create calibrator movements',
                'edit calibrator movements',
                'delete calibrator movements',
            ],
        ],
        'calibration_certificates' => [
            'module' => 'metrology',
            'entity' => 'calibration certificates',
            'icon' => 'fa-certificate',
            'lang' => 'Calibration Certificates',
            'perms' => [
                'view calibration certificates',
                'create calibration certificates',
                'edit calibration certificates',
                'delete calibration certificates',
            ],
        ],
        'quantities_units' => [
            'module' => 'metrology',
            'entity' => 'quantities units',
            'icon' => 'fa-balance-scale',
            'lang' => 'Quantities & Units',
            'perms' => [
                'view quantities units',
                'create quantities units',
                'edit quantities units',
                'delete quantities units',
            ],
        ],
        'reports' => [
            'module' => 'metrology',
            'entity' => 'reports',
            'icon' => 'fa-file-alt',
            'lang' => 'Reports Management',
            'perms' => [
                'view reports',
                'create reports',
                'edit reports',
                'delete reports',
            ],
        ],

        // 💼 Operations & Projects
        'missions' => [
            'module' => 'operations',
            'entity' => 'missions',
            'icon' => 'fa-tasks',
            'lang' => 'Mission Management',
            'perms' => [
                'view missions',
                'create missions',
                'edit missions',
                'delete missions',
            ],
        ],
        'contracts' => [
            'module' => 'operations',
            'entity' => 'contracts',
            'icon' => 'fa-file-contract',
            'lang' => 'Contracts',
            'perms' => [
                'view contracts',
                'create contracts',
                'edit contracts',
                'delete contracts',
            ],
        ],
        'attachments' => [
            'module' => 'operations',
            'entity' => 'attachments',
            'icon' => 'fa-paperclip',
            'lang' => 'Attachments List',
            'perms' => [
                'view attachments',
                'create attachments',
                'edit attachments',
                'delete attachments',
            ],
        ],
        'article_types' => [
            'module' => 'operations',
            'entity' => 'article types',
            'icon' => 'fa-boxes',
            'lang' => 'Classification of Articles',
            'perms' => [
                'view article types',
                'create article types',
                'edit article types',
                'delete article types',
            ],
        ],

        // 💰 Financial Management
        'warranties' => [
            'module' => 'financial',
            'entity' => 'warranties',
            'icon' => 'fa-shield-alt',
            'lang' => 'Bank Guarantees',
            'perms' => [
                'view warranties',
                'create warranties',
                'edit warranties',
                'delete warranties',
            ],
        ],
        'expenses' => [
            'module' => 'financial',
            'entity' => 'expenses',
            'icon' => 'fa-money-bill-wave',
            'lang' => 'Expenses & Charges',
            'perms' => [
                'view expenses',
                'create expenses',
                'edit expenses',
                'delete expenses',
            ],
        ],

        // 📈 Internal and Analytical Management
        'annual_forecasts' => [
            'module' => 'analytics',
            'entity' => 'annual forecasts',
            'icon' => 'fa-calendar-alt',
            'lang' => 'Annual Forecasts',
            'perms' => [
                'view annual forecasts',
                'create annual forecasts',
                'edit annual forecasts',
                'delete annual forecasts',
            ],
        ],
        'company_statistics' => [
            'module' => 'analytics',
            'entity' => 'company statistics',
            'icon' => 'fa-chart-bar',
            'lang' => 'Company Statistics',
            'perms' => [
                'view company statistics',
                'create company statistics',
                'edit company statistics',
                'delete company statistics',
            ],
        ],

        // 🗂️ Master Data / Reference Data
        'clients' => [
            'module' => 'master_data',
            'entity' => 'clients',
            'icon' => 'fa-user-tie',
            'lang' => 'Clients',
            'perms' => [
                'view clients',
                'create clients',
                'edit clients',
                'delete clients',
            ],
        ],
        'employees' => [
            'module' => 'master_data',
            'entity' => 'employees',
            'icon' => 'fa-id-badge',
            'lang' => 'Employees',
            'perms' => [
                'view employees',
                'create employees',
                'edit employees',
                'delete employees',
                'view employee compensation',
            ],
        ],
        'sites' => [
            'module' => 'master_data',
            'entity' => 'sites',
            'icon' => 'fa-map-marker-alt',
            'lang' => 'Sites',
            'perms' => [
                'view sites',
                'create sites',
                'edit sites',
                'delete sites',
            ],
        ],
    ],
];
