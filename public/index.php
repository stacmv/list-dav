<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ListDAV\Core\Application;

$app = new Application(dirname(__DIR__));
$app->ensureAdminUser();
$app->run();
