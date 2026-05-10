<?php
$dirs = ['app', 'routes', 'config'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    $it = new RecursiveDirectoryIterator($dir);
    foreach (new RecursiveIteratorIterator($it) as $file) {
        if ($file->getExtension() === 'php') {
            $path = $file->getPathname();
            $content = file_get_contents($path);
            if (strpos($content, "\r") !== false) {
                file_put_contents($path, str_replace("\r", "", $content));
                echo "Cleaned: $path\n";
            }
        }
    }
}
echo "Cleanup complete.\n";
