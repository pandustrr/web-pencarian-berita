<?php

// Minimal Laravel bootstrap untuk testing
define('LARAVEL_START', microtime(true));
require __DIR__ . '/bootstrap/app.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\News;

// Test query
$news = News::find(1);

if ($news) {
    echo "SUCCESS!\n";
    echo "ID: " . $news->id . "\n";
    echo "Title: " . $news->title . "\n";
    echo "Has original_text: " . (isset($news->original_text) ? 'YES' : 'NO') . "\n";
    echo "original_text is empty: " . (empty($news->original_text) ? 'YES' : 'NO') . "\n";
    echo "Text length: " . strlen($news->original_text) . "\n";
    echo "First 100 chars: " . substr($news->original_text, 0, 100) . "\n";
} else {
    echo "News ID 1 not found\n";
}
?>
