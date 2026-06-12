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

    // SSO external service used by the application
    'sso' => [
        'base_url' => env('SSO_BASE_URL', 'https://iae-sso.virtualfri.id'),
        // relative endpoints (will be concatenated with base_url in the service)
        'token_endpoint' => env('SSO_TOKEN_ENDPOINT', '/api/v1/auth/token'),
        'jwks_endpoint' => env('SSO_JWKS_ENDPOINT', '/api/v1/auth/jwks'),
        'cache_prefix' => env('SSO_CACHE_PREFIX', 'sso'),
        'jwks_cache_seconds' => env('SSO_JWKS_CACHE_SECONDS', 3600),
        // Optional fallback JWKS JSON or single public key PEM in env
        'jwks_fallback_json' => env('SSO_JWKS_JSON', null),
        'public_key' => env('SSO_PUBLIC_KEY', null),
    ],

];
