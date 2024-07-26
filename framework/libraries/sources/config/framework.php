<?php

return [
    'paths' => [
        'root' => realpath(__DIR__ . '/../../../'),
    ],
    'files' => [
        'helper' => realpath(__DIR__ . '/../../../helpers').'/helper.php',
        'aliases' => realpath(__DIR__ ). '/aliases.php',
        'console' => realpath(__DIR__ . '/../../../console').'/kernel.php'
    ],
];
