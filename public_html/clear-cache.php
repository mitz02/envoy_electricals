<?php

use Illuminate\Contracts\Console\Kernel;

// Place this file in your public_html folder and access via browser
// Delete immediately after use!

// Laravel project is in /home/username/envoy (outside public_html)
// This script is in /home/username/public_html/clear-cache.php
$laravelRoot = realpath(__DIR__.'/../envoy');

if (! $laravelRoot || ! file_exists($laravelRoot.'/artisan')) {
    // Try common alternative paths
    $paths = [
        __DIR__.'/../envoy',
        __DIR__.'/../../envoy',
        '/home/'.get_current_user().'/envoy',
        '/var/www/envoy',
    ];
    foreach ($paths as $p) {
        if (file_exists($p.'/artisan')) {
            $laravelRoot = $p;
            break;
        }
    }
}

if (! $laravelRoot || ! file_exists($laravelRoot.'/vendor/autoload.php')) {
    exit('<h1>Error</h1><p>Laravel root not found. Checked: '.htmlspecialchars(implode(', ', $paths)).'</p>');
}

require $laravelRoot.'/vendor/autoload.php';

$app = require_once $laravelRoot.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);

$commands = [
    'config:clear',
    'route:clear',
    'view:clear',
    'cache:clear',
    'optimize:clear',
];

echo '<h1>Clearing Laravel Caches</h1>';
echo '<p>Laravel root: <code>'.htmlspecialchars($laravelRoot).'</code></p>';
echo '<pre>';
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
