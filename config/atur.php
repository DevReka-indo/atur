<?php

return [
    'application_name' => 'ATUR',
    'tagline' => 'Platform Manajemen Project & Workspace',
    'description' => 'Platform untuk merencanakan pekerjaan, mengelola kolaborasi tim, dan memantau progres project dalam satu tempat.',
    'version' => '2.14.0',
    'environment_label' => 'Production',
    'release_year' => 2026,
    'developer' => 'PT Rekaindo Global Jasa',
    'support_email' => 'helpdeskit.reka@gmail.com',
    'license' => 'Internal Use',
    'privacy_version' => '1.0',
    'privacy_effective_date' => '1 Januari 2026',
    'workload' => [
        'default_period' => 'next_7_days',
        'active_task_statuses' => ['to_do', 'in_progress', 'review'],
        'active_project_statuses' => ['active', 'urgent'],
        'thresholds' => [
            'attention' => 5,
            'high_risk' => 7,
            'critical' => 9,
        ],
        'custom_range_max_days' => 366,
        'per_page' => 15,
    ],
];
