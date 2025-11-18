#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Development server launcher
 * Usage: php bin/server.php [port]
 */

$port = $argv[1] ?? 8080;
$host = '127.0.0.1';
$docroot = __DIR__ . '/../public';

echo "ListDAV Development Server\n";
echo "==========================\n";
echo "Server running at http://{$host}:{$port}\n";
echo "Document root: {$docroot}\n";
echo "Press Ctrl+C to stop\n\n";

// Check if public/index.php exists
if (!file_exists($docroot . '/index.php')) {
    echo "Error: {$docroot}/index.php not found\n";
    exit(1);
}

// Check if vendor/autoload.php exists
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "Error: vendor/autoload.php not found\n";
    echo "Run 'composer install' first\n";
    exit(1);
}

// Start PHP built-in server
$command = sprintf(
    'php -S %s:%s -t %s',
    $host,
    $port,
    escapeshellarg($docroot)
);

passthru($command);
