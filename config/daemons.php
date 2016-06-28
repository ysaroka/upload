<?php

return [

    'wsserver' => [
        'host' => env('WSSERVER_HOST', 'localhost'),
        'port' => env('WSSERVER_PORT', 8080),
        'ips' => env('WSSERVER_IPS', '0.0.0.0'),
        'path' => env('WSSERVER_PATH', 'broker'),
    ],

];
