<?php

/*
|--------------------------------------------------------------------------
| Workspace modules
|--------------------------------------------------------------------------
| لإضافة وحدة جديدة أضف عنصراً هنا فقط، وستظهر البطاقة تلقائياً
| لمن يملك الصلاحية المحددة.
|
| ملاحظة: النصوص (title / description) تُكتب بالإنجليزية كمفاتيح ترجمة،
| ويُطبَّق عليها __() داخل المكوّن وقت العرض.
*/

return [

    'modules' => [
        [
            'permission' => 'view metrology',
            'icon' => 'module-metrology',
            'route' => 'dashboard_metrology',
            'title' => 'Dashboard Metrology',
            'description' => 'Manage measuring instruments, technical equipment, calibrator movements, and calibration certificates.',
            'explorers' => 5, // عدّل الرقم عند إضافة/حذف صفحة استكشاف
        ],
        [
            'permission' => 'view operations',
            'icon' => 'module-operations',
            'route' => 'dashboard_operations',
            'title' => 'Dashboard Operations',
            'description' => 'Coordinate operational field missions, manage enterprise contracts.',
            'explorers' => 5,
        ],
        [
            'permission' => 'view analytics',
            'icon' => 'module-analytics',
            'route' => 'dashboard_analytics',
            'title' => 'Dashboard Analytics',
            'description' => 'Track financial expenditures, analyze annual performance forecasts, company metrics, and executive reports.',
            'explorers' => 4,
        ],
        [
            'permission' => 'view master data',
            'icon' => 'module-master-data',
            'route' => 'dashboard_master_data',
            'title' => 'Dashboard Master Data',
            'description' => 'Maintain foundational reference data including clients, employees, sites, units, and article classifications.',
            'explorers' => 3,
        ],
    ],

];
