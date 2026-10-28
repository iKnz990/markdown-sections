<?php

declare(strict_types=1);

$checked = 0;
foreach (['src', 'tests', 'examples', 'tools'] as $directory) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../' . $directory));
    foreach ($files as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $output = [];
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()) . ' 2>&1', $output, $exitCode);
        if ($exitCode !== 0) {
            fwrite(STDERR, implode(PHP_EOL, $output) . PHP_EOL);
            exit($exitCode);
        }

        $checked++;
    }
}

if ($checked === 0) {
    fwrite(STDERR, "No PHP files found for syntax checking.\n");
    exit(1);
}

echo sprintf("PHP syntax valid in %d files.\n", $checked);
