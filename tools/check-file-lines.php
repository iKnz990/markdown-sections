<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php tools/check-file-lines.php <path> [<path> ...]\n");
    exit(2);
}

$limit = 420;
$failures = [];

foreach (array_slice($argv, 1) as $path) {
    if (! is_dir($path)) {
        continue;
    }

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    foreach ($files as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $lineCount = count(file($file->getPathname()) ?: []);
        if ($lineCount > $limit) {
            $failures[] = sprintf('%s has %d lines', $file->getPathname(), $lineCount);
        }
    }
}

if ($failures !== []) {
    fwrite(STDERR, "Files exceed {$limit} lines:\n" . implode("\n", $failures) . "\n");
    exit(1);
}

echo "All checked PHP files are {$limit} lines or fewer.\n";
