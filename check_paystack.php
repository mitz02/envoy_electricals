<?php

use App\Services\PaystackService;

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$service = new PaystackService;
echo 'Configured: '.($service->isConfigured() ? 'YES' : 'NO').PHP_EOL;

// Try to initialize a test payment
try {
    $response = $service->initialize(
        'TEST-'.time(),
        1000,
        'test@example.com',
        'http://localhost:8000/test-callback',
        ['test' => true]
    );
    echo 'Test Payment Initialize: SUCCESS'.PHP_EOL;
    echo 'Authorization URL: '.($response['data']['authorization_url'] ?? 'N/A').PHP_EOL;
} catch (Throwable $e) {
    echo 'Test Payment Initialize: FAILED'.PHP_EOL;
    echo 'Error: '.$e->getMessage().PHP_EOL;
    if (method_exists($e, 'response') && $e->response) {
        $json = $e->response->json();
        echo 'Response: '.json_encode($json).PHP_EOL;
    }
}
