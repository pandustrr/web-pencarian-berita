<?php

require __DIR__ . '/../../bootstrap/app.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\News;

// Test: Get first news with content
$news = News::where('original_text', '!=', '')->first();

if ($news) {
    echo "✅ News found!\n";
    echo "ID: " . $news->id . "\n";
    echo "Title: " . $news->title . "\n";
    echo "Text Length: " . strlen($news->original_text) . " chars\n";
    echo "Text Preview: " . substr($news->original_text, 0, 100) . "...\n";
    echo "Category: " . $news->category . "\n";
} else {
    echo "❌ No news found\n";
}
?>
