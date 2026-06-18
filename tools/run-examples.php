<?php

declare(strict_types=1);

$examples = glob(__DIR__ . '/../EXAMPLE/*.php') ?: [];
sort($examples);

foreach ($examples as $example) {
    assertValidSyntax($example);
    runExample($example);
}

echo sprintf("Ran %d examples successfully.\n", count($examples));

function assertValidSyntax(string $example): void
{
    $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($example);
    exec($command, $output, $exitCode);

    if ($exitCode !== 0) {
        fwrite(STDERR, implode(PHP_EOL, $output) . PHP_EOL);
        exit($exitCode);
    }
}

function runExample(string $example): void
{
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($example);
    exec($command, $output, $exitCode);

    if ($exitCode !== 0) {
        fwrite(STDERR, sprintf("Example failed: %s\n", basename($example)));
        fwrite(STDERR, implode(PHP_EOL, $output) . PHP_EOL);
        exit($exitCode);
    }
}
