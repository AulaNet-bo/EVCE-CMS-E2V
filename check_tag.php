<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RfidTag;
use App\Models\User;

$tagCode = 'AABBCCDD';
$tag = RfidTag::where('tag_code', $tagCode)->first();

echo "Checking Tag: $tagCode\n";
if ($tag) {
    echo "Found Tag: " . $tag->tag_code . "\n";
    if ($tag->user) {
        echo "Linked User: " . $tag->user->name . " (ID: " . $tag->user->id . ")\n";
    } else {
        echo "User: NONE (Unassigned)\n";
    }
} else {
    echo "Tag NOT Found in CMS.\n";
}
