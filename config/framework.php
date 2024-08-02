<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Root Path
    |--------------------------------------------------------------------------
    |
    | Di sini Anda dapat mendefinisikan jalur root untuk aplikasi Anda. Jalur ini
    | akan digunakan sebagai acuan untuk jalur-jalur lainnya dalam aplikasi.
    |
    */
    'paths' => [
        'root' => dirname(__DIR__, 3), // Jalur root aplikasi
    ],

    /*
    |--------------------------------------------------------------------------
    | File Paths
    |--------------------------------------------------------------------------
    |
    | Di sini Anda dapat mendefinisikan jalur untuk file-file penting dalam
    | aplikasi Anda, seperti file helper dan kernel console. Anda bisa
    | menyesuaikan jalur ini sesuai dengan struktur direktori aplikasi Anda.
    |
    */
    'files' => [
        'helper'  => realpath(__DIR__ . '/../foundation') . '/helpers.php', // Jalur ke file helper
        'console' => realpath(__DIR__ . '/../console') . '/kernel.php', // Jalur ke file kernel console
    ],
];
