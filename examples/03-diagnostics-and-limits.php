<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Nocturnal\MarkdownSections\MarkdownSections;
use Nocturnal\MarkdownSections\Parser\MarkdownSectionParser;
use Nocturnal\MarkdownSections\Parser\ParserLimits;
use Nocturnal\MarkdownSections\Value\Diagnostic;

$malformed = <<<'MD'
:::callout{title "Missing equals" variant=info}
This section is kept, but the bad attribute is reported.
:::

:::faq
This section never closes.
MD;

$document = MarkdownSections::parse($malformed);

echo "Parsed sections: " . count($document->sections()) . PHP_EOL;
echo "Diagnostics:" . PHP_EOL;

foreach ($document->diagnostics() as $diagnostic) {
    printDiagnostic($diagnostic);
}

echo PHP_EOL . "Section count limit:" . PHP_EOL;

$limitedParser = new MarkdownSectionParser(new ParserLimits(
    maxContentSize: 1_048_576,
    maxSections: 1,
    maxSectionSize: 102_400,
));

$limitedDocument = $limitedParser->parse(":::one\nA\n:::\n\n:::two\nB\n:::");
echo "Sections kept: " . count($limitedDocument->sections()) . PHP_EOL;
foreach ($limitedDocument->diagnostics() as $diagnostic) {
    printDiagnostic($diagnostic);
}

echo PHP_EOL . "Document size limit:" . PHP_EOL;

try {
    MarkdownSections::parse('too large', new ParserLimits(3, 100, 100));
} catch (\InvalidArgumentException $exception) {
    echo $exception->getMessage() . PHP_EOL;
}

function printDiagnostic(Diagnostic $diagnostic): void
{
    echo "- [{$diagnostic->code}] line {$diagnostic->line}: {$diagnostic->message}" . PHP_EOL;
}
