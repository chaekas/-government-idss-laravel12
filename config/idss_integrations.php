<?php

return [
    'providers' => [
        'utambuzi' => [
            'enabled' => (bool) env('IDSS_UTAMBUZI_ENABLED', false),
            'adapter' => env('IDSS_UTAMBUZI_ADAPTER'),
        ],
        'sms' => [
            'enabled' => (bool) env('IDSS_SMS_ENABLED', false),
            'adapter' => env('IDSS_SMS_ADAPTER'),
        ],
        'email' => [
            'enabled' => (bool) env('IDSS_EMAIL_ENABLED', false),
            'adapter' => env('IDSS_EMAIL_ADAPTER', App\Integrations\EmailNotificationAdapter::class),
        ],
    ],
];
