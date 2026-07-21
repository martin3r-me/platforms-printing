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

    /*
    |--------------------------------------------------------------------------
    | Zeichenkodierung für den Druck
    |--------------------------------------------------------------------------
    | Star/Epson CloudPRNT-Drucker interpretieren text/plain in ihrer
    | eingestellten Zeichentabelle (Codepage), NICHT in UTF-8. Der Inhalt wird
    | daher vor dem Ausliefern in diese Codepage umgewandelt.
    |
    | Muss zur Drucker-Einstellung passen. Übliche Werte:
    |   CP1252  (Windows-1252) – deckt deutsche Umlaute/ß/€ ab (Default)
    |   CP850   – DOS Westeuropa (viele Bondrucker)
    |   CP858   – wie CP850 inkl. €
    |   CP437   – DOS US
    |   UTF-8   – nur wenn der Drucker echtes UTF-8 kann
    */
    'encoding' => [
        'codepage' => env('PRINTING_CODEPAGE', 'CP1252'),
    ],

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
        'middleware' => [],
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
