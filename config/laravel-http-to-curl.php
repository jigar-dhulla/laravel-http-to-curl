<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Log Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for request logging
    |
    */
    'logging' => [
        'enabled' => env('HTTP_TO_CURL_LOGGING', false),
        'log_level' => env('HTTP_TO_CURL_LOG_LEVEL', 'debug'),
        'channel' => env('HTTP_TO_CURL_LOG_CHANNEL', 'stack'),
    ],
];
