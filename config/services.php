<?php

return [
    /*
    |--------------------------------------------------------------------------
    | BiblioCommons API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for BiblioCommons API integration including endpoints,
    | authentication keys, and library identifiers.
    |
    */

    'bibliocommons' => [
        // Base URL for BiblioCommons API v1
        'api_url' => env('BIBLIOCOMMONS_API_BASE_URL', 'https://api.bibliocommons.com').'/v1',

        // API key for BiblioCommons templates API
        'api_key' => env('BIBLIOCOMMONS_API_KEY'),

        // API key for BiblioCommons titles API (can be same as templates API key)
        'titles_api_key' => env('BIBLIOCOMMONS_TITLES_API_KEY', env('BIBLIOCOMMONS_API_KEY')),

        // Library identifier for API requests
        'library_id' => env('BIBLIOCOMMONS_LIBRARY_ID', 'tpl'),

        // Base URL for BiblioCommons SSO API (may be different from titles API)
        'api_base_url' => env('BIBLIOCOMMONS_API_BASE_URL', 'https://api.bibliocommons.com'),

        // URL for external templates API
        'external_templates_url' => env('BIBLIOCOMMONS_API_URL'),

        // Login URL for BiblioCommons SSO
        'login_url' => env('BIBLIOCOMMONS_LOGIN_URL'),

        // Session cookie name for BiblioCommons
        'session_cookie' => env('BIBLIO_SESSION_COOKIE', 'bc_session'),

        // Additional branches that may not be present in API response
        // These will be merged into the locations/branches list when appropriate
        'additional_branches' => [
            [
                'id' => 'BKONE',
                'name' => 'Bookmobile',
                'type' => 'branch',
            ],
        ],
    ],
];
