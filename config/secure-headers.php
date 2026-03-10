<?php

return [
    // Safe Mode
    'safeMode' => false,

    // HSTS Strict-Transport-Security
    'hsts' => [
        'enabled' => true,
    ],

    // Content Security Policy
    'csp' => [
        'default-src' => [
            '*',
            'https://www.google.com',
            'https://www.gstatic.com'
        ],
        'script-src' => [
            'self',
            'unsafe-inline', // Allow inline script
            'unsafe-eval',
            'https://www.google.com',
            'https://www.gstatic.com',
            'https://connect.facebook.net',
            'https://code.highcharts.com',
            'https://code.jquery.com',
            'https://cdn.jsdelivr.net',
            'https://cdn.datatables.net',
            'https://ajax.googleapis.com',
            'https://platform-api.sharethis.com',
            'https://buttons-config.sharethis.com'
        ],
        'img-src' => [
            '*', // Allow images from anywhere
            'self data:'
        ],
        'style-src' => [
            'self',
            'unsafe-inline', // Allow inline styles
            'https://fonts.googleapis.com', // Allow stylesheets from Google Fonts
            'https://cdnjs.cloudflare.com',
            'https://maxcdn.bootstrapcdn.com',
            'https://cdn.jsdelivr.net',
            'https://code.jquery.com',
            'https://cdn.datatables.net'
        ],
        'font-src' => [
            'self',
            'https://fonts.gstatic.com', // Allow fonts from the Google Fonts CDN
            'https://maxcdn.bootstrapcdn.com',
            'https://cdnjs.cloudflare.com'
        ],
    ],
];