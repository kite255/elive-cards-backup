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

    'elive_sms' => [
        'base_url' => env('ELIVE_SMS_BASE_URL', 'https://message.elive.co.tz/api/v1/vendor'),
        'api_key' => env('ELIVE_SMS_API_KEY'),
        'secret_key' => env('ELIVE_SMS_SECRET_KEY'),
        'sender_id' => env('ELIVE_SMS_SENDER_ID', 'eLive Card'),
    ],

    // 'whatsapp' => [
    //     'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
    //     'api_url' => 'https://graph.facebook.com/v22.0/589829680877901/messages',
    // ],

];