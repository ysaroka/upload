<?php

return [
    'connection' => [
        'host' => env('AMQP_CONNECTION_HOST', 'localhost'),
        'port' => env('AMQP_CONNECTION_PORT', 5672),
        'user' => env('AMQP_CONNECTION_USER', 'guest'),
        'password' => env('AMQP_CONNECTION_PASSWORD', 'guest'),

    ],

    'queues' => [
        'uploader' => [
            'upload' => env('AMQP_QUEUE_UPLOADER_UPLOAD', 'uploader.upload'),
            'progress' => env('AMQP_QUEUE_UPLOADER_PROGRESS', 'uploader.progress'),
        ],
    ],
];
