<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    /*
    |--------------------------------------------------------------------------
    | HollaTags SMS Configuration
    |--------------------------------------------------------------------------
    */
    'hollatags' => [
        'base_url' => env('HOLLATAGS_API_URL', 'https://sms.hollatags.com/api'),
        'api_key' => env('HOLLATAGS_API_KEY'),
        'default_sender_id' => env('HOLLATAGS_DEFAULT_SENDER_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Flutterwave Configuration
    |--------------------------------------------------------------------------
    */
    'flutterwave' => [
        'base_url' => env('FLUTTERWAVE_API_URL', 'https://api.flutterwave.com/v3'),
        'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
        'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
        'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY'),
        'webhook_hash' => env('FLUTTERWAVE_WEBHOOK_HASH'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Messaging Configuration
    |--------------------------------------------------------------------------
    */
    'messaging' => [
        'cost_per_segment' => env('MESSAGING_COST_PER_SEGMENT', 5.99),
        'max_segments_per_message' => env('MESSAGING_MAX_SEGMENTS', 10),
        'retry_attempts' => env('MESSAGING_RETRY_ATTEMPTS', 3),
        'retry_delay_seconds' => env('MESSAGING_RETRY_DELAY', 60),
    ],


    'cron' => [
        'secret_token' => env('CRON_SECRET_TOKEN'),
    ],

];
