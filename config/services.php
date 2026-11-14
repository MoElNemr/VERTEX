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

    'whatsapp' => [
        'service_url' => env('WHATSAPP_SERVICE_URL', 'http://127.0.0.1:3000'),
        'api_key' => env('WHATSAPP_SERVER_API_KEY', env('WHATSAPP_API_KEY')),
        'webhook_secret' => env('WHATSAPP_WEBHOOK_SECRET'),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'url' => env('OPENAI_API_URL', 'https://api.openai.com/v1'),
    ],

    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'url' => env('GROQ_API_URL', 'https://api.groq.com/openai/v1'),
    ],

    'fawry' => [
        'mode' => env('FAWRY_MODE', 'sandbox'),
        'merchant_code' => env('FAWRY_MERCHANT_CODE', '1014316'),
        'security_key' => env('FAWRY_SECURITY_KEY', 'default_test_key'),
        'merchant_wallet' => env('FAWRY_MERCHANT_WALLET', ''),
    ],

    'meta' => [
        'app_id' => env('META_APP_ID', env('FACEBOOK_CLIENT_ID')),
        'client_id' => env('META_CLIENT_ID', env('META_APP_ID', env('FACEBOOK_CLIENT_ID'))),
        'app_secret' => env('META_APP_SECRET', env('FACEBOOK_CLIENT_SECRET')),
        'client_secret' => env('META_CLIENT_SECRET', env('META_APP_SECRET', env('FACEBOOK_CLIENT_SECRET'))),
        'redirect_uri' => env('META_REDIRECT_URI', env('FACEBOOK_REDIRECT_URI')),
        'verify_token' => env('META_VERIFY_TOKEN'),
        'scopes' => env('META_SCOPES'),
    ],

];
