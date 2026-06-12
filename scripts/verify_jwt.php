<?php

// Bootstrap Laravel and verify a JWT using App\Services\SsoService
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$token = $argv[1] ?? getenv('TOKEN') ?? '';
if (empty($token)) {
    echo "Usage: php scripts/verify_jwt.php <token>\n";
    exit(2);
}

try {
    /** @var \App\Services\SsoService $s */
    $s = $app->make(\App\Services\SsoService::class);
    $decoded = $s->verifyToken($token);
    // decoded is an object (stdClass) or array depending on JWT library
    echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} catch (Exception $e) {
    echo 'ERR: ' . $e->getMessage() . "\n";
    exit(1);
}
