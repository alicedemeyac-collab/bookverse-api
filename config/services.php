<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google_books' => [
        'key' => env('GOOGLE_BOOKS_API_KEY'),
        'base_url' => env('GOOGLE_BOOKS_BASE_URL', 'https://www.googleapis.com/books/v1'),
        'timeout' => 8,              // secondes
        'retry_sleep' => 250,        // millisecondes entre deux tentatives
        'cache_ttl' => 3600,         // recherches : 1 heure
        'detail_cache_ttl' => 86400, // détails d'un livre : 24 heures
        'per_minute' => 30,
        'per_day' => (int) env('GOOGLE_BOOKS_DAILY_LIMIT', 900),
    ],

];
