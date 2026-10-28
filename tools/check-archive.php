<?php

declare(strict_types=1);

if ($argc !== 2) {
    fwrite(STDERR, "Usage: php tools/check-archive.php <release.zip>\n");
    exit(2);
}

if (! class_exists(ZipArchive::class)) {
    fwrite(STDERR, "The archive check requires the PHP zip extension.\n");
    exit(2);
}

$archive = new ZipArchive();
if ($archive->open($argv[1]) !== true) {
    fwrite(STDERR, "Cannot open release archive.\n");
    exit(1);
}

$names = [];
$failures = [];
for ($index = 0; $index < $archive->numFiles; $index++) {
    $name = $archive->getNameIndex($index);
    if ($name === false) {
        $failures[] = 'Unreadable archive entry.';
        continue;
    }

    $names[] = $name;
    if (preg_match('~(?:^|/)(?:\.git|\.github|vendor|\.phpunit\.cache|\.phpstan\.cache|coverage|build|\.idea|\.vscode)(?:/|$)|(?:^|/)(?:composer\.lock|\.phpunit\.result\.cache|\.env(?:\..*)?|.*\.(?:log|tmp)|\.DS_Store|Thumbs\.db)$~', $name) === 1) {
        $failures[] = 'Unexpected local or development-state entry: ' . $name;
    }

    if (str_contains($name, '\\') || str_starts_with($name, '/') || preg_match('~(?:^|/)\.\.(?:/|$)|:~', $name) === 1) {
        $failures[] = 'Unsafe archive path: ' . $name;
    }
}

foreach (['LICENSE', 'THIRD_PARTY_NOTICES.md', 'README.md', 'composer.json', 'src/MarkdownSections.php',
    'tests/MarkdownDocumentTest.php', 'tools/check-syntax.php', 'phpunit.xml', 'phpstan.neon',
    'examples/01-parse-and-inspect.php'] as $required) {
    if (! in_array($required, $names, true)) {
        $failures[] = 'Missing release file: ' . $required;
    }
}

$archive->close();
if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, array_slice($failures, 0, 20)) . PHP_EOL);
    if (count($failures) > 20) {
        fwrite(STDERR, sprintf("%d additional archive issues.\n", count($failures) - 20));
    }
    exit(1);
}

echo sprintf("Release archive contains %d files; required source, notices and QA files are present; local state excluded.\n", count($names));
