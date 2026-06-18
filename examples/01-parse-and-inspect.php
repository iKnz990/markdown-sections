<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Nocturnal\MarkdownSections\MarkdownSections;
use Nocturnal\MarkdownSections\Value\Section;

$markdown = <<<'MD'
# Product Launch

Intro copy stays untouched.

:::callout{variant="info" title="Use \"quotes\"" audience=consumer audience=editor}
## Launch note

- Parse sections.
- Preserve Markdown.
- Keep source positions.
:::

:::product-card{sku=nocturnal-parser url="https://example.com/parser?ref=docs"}
### Markdown Sections

Reusable fenced content blocks.
:::
MD;

$document = MarkdownSections::parse($markdown);

echo "Total sections: " . count($document->sections()) . PHP_EOL;
echo "Callout sections: " . count($document->findByType('callout')) . PHP_EOL;
echo PHP_EOL;

foreach ($document->sections() as $section) {
    describeSection($section);
}

function describeSection(Section $section): void
{
    echo "Section #{$section->index}" . PHP_EOL;
    echo "Type: {$section->type}" . PHP_EOL;
    echo "Lines: {$section->span->startLine}-{$section->span->endLine}" . PHP_EOL;
    echo "Offsets: {$section->span->startOffset}-{$section->span->endOffset}" . PHP_EOL;
    echo "Has title: " . ($section->hasAttribute('title') ? 'yes' : 'no') . PHP_EOL;
    echo "Variant: " . $section->getAttribute('variant', 'none') . PHP_EOL;
    echo "Attributes:" . PHP_EOL;

    foreach ($section->attributes->all() as $key => $value) {
        echo "  - {$key}: {$value}" . PHP_EOL;
    }

    echo "Content preview: " . str_replace("\n", ' | ', trim($section->content)) . PHP_EOL;
    echo "Raw block bytes: " . strlen($section->rawBlock) . PHP_EOL;
    echo PHP_EOL;
}
