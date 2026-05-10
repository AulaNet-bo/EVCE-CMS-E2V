<?php
// 1. Clean Files (\r)
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
                echo "Cleaned file: $path\n";
            }
        }
    }
}

// 2. Clean Database
try {
    $pdo = new PDO('mysql:host=mysql-local;dbname=stevedb', 'steve', 'changeme');
    $pdo->exec("UPDATE system_settings SET logo_path = NULL");
    $pdo->exec("UPDATE rfid_tags SET tag_code = REPLACE(tag_code, '\r', '')");
    $pdo->exec("UPDATE users SET name = REPLACE(name, '\r', ''), email = REPLACE(email, '\r', '')");
    echo "Database cleaned successfully.\n";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

echo "Final Cleanup Complete.\n";
