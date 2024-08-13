<?php

function recurse_copy($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst, 0777, true);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                recurse_copy($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

$directories_to_copy = [
    'Bus',
    'Collections',
    'Conditionable',
    'Config',
    'Container',
    'Contracts',
    'Database',
    'Events',
    'Filesystem',
    'Http',
    'Log',
    'Macroable',
    'Pipeline',
    'Routing',
    'Session',
    'Support',
    'Translation',
    'Validation',
    'View'
];

$src_base = 'laravel-framework/src/Illuminate';
$dst_base = 'Libraries/illuminate';

foreach ($directories_to_copy as $dir) {
    $src = $src_base . '/' . $dir;
    $dst = $dst_base . '/' . $dir;
    recurse_copy($src, $dst);
    echo "Copied $src to $dst\n";
}
