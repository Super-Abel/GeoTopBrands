<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    |
    | Here you can configure the default pagination settings for your application.
    |
    */
    'pagination' => [
        'per_page' => 5,
        'max_per_page' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Geolocation Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for geolocation-based features.
    |
    */
    'geolocation' => [
        'default_country' => 'INT',
        'header_name' => 'CF-IPCountry',
        'supported_countries' => ['FR', 'CM', 'EN'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for various cache durations in the application.
    |
    */
    'cache' => [
        'brands' => [
            'duration' => 300, // 5 minutes in seconds
        ],
    ],
];
