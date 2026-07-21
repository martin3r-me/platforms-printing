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

        /*
        | Roher Steuerbefehl (Hex), der jedem Druckauftrag vorangestellt wird,
        | um den Drucker auf einen definierten Zeichensatz zu zwingen.
        |
        | Hintergrund: Steht der Drucker auf "International Character Set =
        | Deutschland" (ISO-646-DE), druckt er @ als §, [ als Ä, \ als Ö usw.,
        | und Umlaute (hohe Bytes) werden falsch dargestellt.
        |
        | Default (StarPRNT):
        |   1B 52 00        ESC R 0   -> Internationaler Zeichensatz = USA
        |                              (@ [ \ ] { | } ~ wieder normal)
        |   1B 1D 74 10     ESC GS t 16 -> Codepage Windows-1252 (WPC1252)
        |
        | Bei Star Line Mode / Epson ESC-POS oder anderem Modell ggf. anpassen,
        | z. B. Epson CP1252: 1B 74 10 (ESC t 16) + 1B 52 00.
        | Leerer String = kein Steuerbefehl.
        */
        'setup_command_hex' => env('PRINTING_SETUP_COMMAND', '1B 52 00 1B 1D 74 10'),
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
