<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

/**
 * Bootstraps Laravel and runs a single artisan command, for hosts with no
 * CLI cron access. Point a web-cron service (cron-job.org, EasyCron, ...) at
 * this script's caller with ?token=<CRON_SECRET> as a query string.
 */
function runCronCommand(string $command): void
{
    header('Content-Type: text/plain; charset=utf-8');

    require __DIR__ . '/../../vendor/autoload.php';
    $app = require __DIR__ . '/../../bootstrap/app.php';

    /** @var Kernel $kernel */
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();

    $secret = config('cron.secret');
    $token = $_GET['token'] ?? '';

    if (!is_string($secret) || $secret === '' || !hash_equals($secret, (string) $token)) {
        http_response_code(403);
        echo "Forbidden\n";
        return;
    }

    $exitCode = Artisan::call($command);
    echo Artisan::output();

    http_response_code($exitCode === 0 ? 200 : 500);
}
