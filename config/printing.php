<?php

return [
    'name' => 'Printing',
    'description' => 'Printing Service',
    'version' => '1.0.0',

    'routing' => [
        'prefix' => 'printing',
        'middleware' => ['web', 'auth'],
    ],

    'guard' => 'web',

    'navigation' => [
        'printing' => [
            'title' => 'Drucken',
            'icon' => 'heroicon-o-printer',
            'route' => 'printing.dashboard',
            'order' => 20,
        ],
    ],

    'sidebar' => [
        'printing' => [
            'title' => 'Drucken',
            'icon' => 'heroicon-o-printer',
            'route' => 'printing.dashboard',
            'order' => 20,
        ],
    ],

    'api' => [
        'prefix' => 'api',
        'middleware' => ['api'],
        'cloudprnt' => [
            'enabled' => true,
            'endpoints' => [
                'poll' => '/poll',
                'job' => '/job/{id}',
                'confirm' => '/confirm/{id}',
            ],
        ],
    ],

    'printers' => [
        'default_username_length' => 8,
        'default_password_length' => 12,
        'auto_generate_credentials' => true,
    ],

    'jobs' => [
        'max_retries' => 3,
        'timeout_minutes' => 30,
        'cleanup_after_days' => 30,
        'statuses' => [
            'pending' => 'Wartend',
            'processing' => 'Wird gedruckt',
            'completed' => 'Gedruckt',
            'failed' => 'Fehlgeschlagen',
            'cancelled' => 'Abgebrochen',
        ],
    ],

    'templates' => [
        'default' => 'default',
        'available' => [
            'default' => 'Standard',
            'deal_details' => 'Deal Details',
            'ticket_summary' => 'Ticket Zusammenfassung',
            'invoice' => 'Rechnung',
            'receipt' => 'Beleg',
        ],
    ],

    'pagination' => [
        'per_page' => env('PRINTING_PER_PAGE', 20),
    ],
];
