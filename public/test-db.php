<?php
require __DIR__.'/../vendor/autoload.php';
\ = require_once __DIR__.'/../bootstrap/app.php';
\->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
header('Content-Type: application/json');
try {
    \ = DB::select('SHOW TABLES');
    echo json_encode([
        'success' => true,
        'database' => DB::getDatabaseName(),
        'connection' => config('database.default'),
        'host' => config('database.connections.mysql.host'),
        'tables' => array_map(function(\) { return array_values((array)\)[0]; }, \)
    ], JSON_PRETTY_PRINT);
} catch (\Exception \) {
    echo json_encode(['success' => false, 'error' => \->getMessage()]);
}
