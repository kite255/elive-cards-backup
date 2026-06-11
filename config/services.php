<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
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

    /*
    |--------------------------------------------------------------------------
    | eLive SMS
    |--------------------------------------------------------------------------
    */

    'elive_sms' => [
        'base_url' => env('ELIVE_SMS_BASE_URL', 'https://message.elive.co.tz/api/v1/vendor'),
        'api_key' => env('ELIVE_SMS_API_KEY'),
        'api_secret' => env('ELIVE_SMS_API_SECRET', env('ELIVE_SMS_SECRET_KEY')),
        'secret_key' => env('ELIVE_SMS_SECRET_KEY', env('ELIVE_SMS_API_SECRET')),
        'sender_id' => env('ELIVE_SMS_SENDER_ID', 'eLive Card'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Cloud API
    |--------------------------------------------------------------------------
    */

    'whatsapp' => [
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', env('WHATSAPP_API_VERSION', 'v23.0')),
        'api_version' => env('WHATSAPP_API_VERSION', env('WHATSAPP_GRAPH_VERSION', 'v23.0')),

        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),

        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),

        'invitation_template' => env('WHATSAPP_INVITATION_TEMPLATE', 'invitation_card_template'),
        'contribution_template' => env('WHATSAPP_CONTRIBUTION_TEMPLATE', 'contribution_card_template'),
        'template_language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'en_GB'),

        'api_url' => env(
            'WHATSAPP_API_URL',
            'https://graph.facebook.com/' .
                env('WHATSAPP_GRAPH_VERSION', env('WHATSAPP_API_VERSION', 'v23.0')) .
                '/' .
                env('WHATSAPP_PHONE_NUMBER_ID') .
                '/messages'
        ),
    ],

];