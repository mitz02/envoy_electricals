<?php

use Illuminate\Contracts\Console\Kernel;

// Place this file in your public folder (public/clear-cache.php) and access via browser
// Delete immediately after use!

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);

$commands = [
    'config:clear',
    'route:clear',
    'view:clear',
    'cache:clear',
    'optimize:clear',
];

echo '<h1>Clearing Laravel Caches</h1><pre>';
foreach ($commands as $cmd) {
    echo "Running: php artisan $cmd\n";
    try {
        $output = $kernel->call($cmd);
        echo "Exit code: $output\n";
    } catch (Throwable $e) {
        echo 'Error: '.$e->getMessage()."\n";
    }
}
echo '</pre><h3>Done! Delete this file immediately.</h3>';
