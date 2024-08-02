// copyLaravelSrc.php
<?php

function recurse_copy($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                recurse_copy($src . '/' . $file, $dst . '/' . $file);
            } else {
                if (copy($src . '/' . $file, $dst . '/' . $file)) {
                    echo "Copied file $src/$file to $dst/$file\n";
                } else {
                    echo "Failed to copy file $src/$file to $dst/$file\n";
                }
            }
        }
    }
    closedir($dir);
}

$src = 'laravel-framework/src';
$dst = 'src';

if (!is_dir($src)) {
    echo "Source directory $src does not exist.\n";
    exit(1);
}

recurse_copy($src, $dst);

if (is_dir($dst)) {
    echo "Laravel framework source files have been copied to src folder.\n";
} else {
    echo "Failed to copy Laravel framework source files.\n";
}
