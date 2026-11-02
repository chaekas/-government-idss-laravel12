<?php

return [
    'roles' => [
        'farmer' => [
            'label' => 'Farmer / Livestock Owner',
            'description' => 'Access own farmer dashboard and notifications; view cases linked to own farms and report disease cases for linked farms.',
        ],
        'field_officer' => [
            'label' => 'Poultry Field Officer (LFO)',
            'description' => 'Register farmers, farms and animals; investigate cases; collect and register samples; update field information; report suspected outbreaks.',
        ],
        'laboratory_technician' => [
            'label' => 'Laboratory Technician',
            'description' => 'View sample custody and laboratory records; receive and assess samples; record test results for accepted samples assigned to the technician.',
        ],
        'laboratory_manager' => [
            'label' => 'Laboratory Manager',
            'description' => 'Review and independently approve or reject submitted laboratory results; oversee laboratory sample workflow.',
        ],
        'regional_veterinary_officer' => [
            'label' => 'Regional Veterinary Officer',
            'description' => 'Access regional surveillance workflows, investigations, outbreak records, reports and GIS.',
        ],
        'zonal_laboratory_administrator' => [
            'label' => 'Zonal Laboratory Administrator',
            'description' => 'Oversee laboratory and sample workflows within the zonal laboratory context.',
        ],
        'mlf_officer' => [
            'label' => 'MLF Officer',
            'description' => 'Access national surveillance reports, GIS, cases, outbreaks, notifications, samples and laboratory results.',
        ],
        'system_admin' => [
            'label' => 'System Administrator',
            'description' => 'Create and deactivate user accounts, assign roles, manage access, review audit records and access administrative functions.',
        ],
    ],
];
