<?php
putenv('DB_HOST=127.0.0.1');
$_ENV['DB_HOST'] = '127.0.0.1';

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
config(['database.connections.mysql.host' => '127.0.0.1']);

$tag = App\Models\RfidTag::where('is_active', true)->first();
if (!$tag) die("No active RFID tag found.\n");

$u = $tag->user;
if (!$u) die("No user associated with tag {$tag->tag_code}.\n");

echo json_encode([
    'id' => $u->id,
    'name' => $u->name,
    'balance' => (float)$u->balance,
    'tag' => $tag->tag_code,
    'tag_id' => $tag->id
], JSON_PRETTY_PRINT);
