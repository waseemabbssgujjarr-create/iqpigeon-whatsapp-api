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

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'price_id' => env('STRIPE_PRICE_ID'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_APP_ID', env('FACEBOOK_CLIENT_ID')),
        'client_secret' => env('FACEBOOK_APP_SECRET', env('FACEBOOK_CLIENT_SECRET')),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
        'config_id' => env('FACEBOOK_LOGIN_CONFIG_ID'),
    ],

    'meta' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'es_config_id' => env('META_CONFIG_ID', env('META_ES_CONFIG_ID')),
        'es_config_id_coexistence' => env('META_ES_CONFIG_ID_COEXISTENCE', env('META_CONFIG_ID_COEXISTENCE')),
        'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN'),
        'graph_version' => env('META_GRAPH_VERSION', 'v21.0'),
        'timeout' => env('META_HTTP_TIMEOUT', 30),
        'user_agent' => env('META_USER_AGENT', 'IQPigeon-WhatsApp-API'),
    ],

    'iqpigeon' => [
        'crm_return_url_allowlist' => array_filter(array_map(
            'trim',
            explode(',', (string) env('IQPIGEON_CRM_RETURN_URL_ALLOWLIST', '')),
        )),
    ],

];
