<?php

return [
    'userfile' => [
        'path' => env('STORAGE_USERFILE_PATH', realpath(base_path() . '/storage/user-files-storage/')),
        'validation' => [
            [
                'extension' => 'txt',
                'max_size' => 10000,
                'stopwords' => 'первая фраза,вторая фраза',
            ],
            [
                'extension' => 'txt',
                'max_size' => 999999,
                'stopwords' => 'это еще одна фраза',
            ],
            [
                'extension' => 'wav',
                'max_size' => 999999999,
            ],
            [
                'extension' => 'avi',
            ],
            [
                'extension' => 'mp4',
            ],
        ],
    ],
];
