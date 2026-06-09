<?php

declare(strict_types=1);

require_once __DIR__ . '/autoload.php';

use App\Infrastructure\Persistence\MySQLServiceRepository;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

try {
    $repository = new MySQLServiceRepository();
    $repository->refreshCategoryCountsCache();

    echo '[' . date('Y-m-d H:i:s') . "] Category counts cache refreshed successfully.\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . '] Error refreshing category counts cache: ' . $exception->getMessage() . "\n");
    exit(1);
}
