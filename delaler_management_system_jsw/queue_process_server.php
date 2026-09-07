<?php

/**
 * Queue Process Server Script
 * 
 * Runs the queue worker process for handling background uploads and asynchronous tasks.
 * Usage: php queue_process_server.php [--queue=default] [--tries=3] [--sleep=3] [--timeout=3600]
 */

define('LARAVEL_START', microtime(true));

// Load Composer Autoloader
require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel Application
$app = require_once __DIR__ . '/bootstrap/app.php';

// Make Artisan Console Kernel
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "====================================================\n";
echo "   Dealer Management System - Queue Process Server  \n";
echo "====================================================\n";
echo "Starting queue worker at: " . date('Y-m-d H:i:s') . "\n";
echo "Press Ctrl+C to stop the process.\n\n";

// Command line parameters or defaults
$queue = 'default';
$tries = 3;
$sleep = 3;
$timeout = 3600;

foreach ($argv as $arg) {
    if (strpos($arg, '--queue=') === 0) {
        $queue = substr($arg, 8);
    } elseif (strpos($arg, '--tries=') === 0) {
        $tries = (int)substr($arg, 8);
    } elseif (strpos($arg, '--sleep=') === 0) {
        $sleep = (int)substr($arg, 8);
    } elseif (strpos($arg, '--timeout=') === 0) {
        $timeout = (int)substr($arg, 10);
    }
}

// Prepare command arguments for queue:work
$commandArgs = [
    'command' => 'queue:work',
    '--queue' => $queue,
    '--tries' => $tries,
    '--sleep' => $sleep,
    '--timeout' => $timeout,
];

// Execute queue:work command
$status = $kernel->call('queue:work', $commandArgs);

exit($status);
