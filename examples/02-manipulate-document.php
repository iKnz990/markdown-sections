<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Nocturnal\MarkdownSections\MarkdownSections;
use Nocturnal\MarkdownSections\Value\SectionAttributes;
use Nocturnal\MarkdownSections\Value\SectionDraft;

$markdown = <<<'MD'
# Campaign

Before text.

:::callout{variant=info title="Original"}
Original content.
:::

Middle text.

:::cta{url="/contact" buttonText="Talk to us"}
Ready to ship?
:::

After text.
MD;

$document = MarkdownSections::parse($markdown);
$callout = $document->findByType('callout')[0];
$cta = $document->findByType('cta')[0];

$updatedCallout = $callout->toDraft()
    ->withAttributes(SectionAttributes::fromArray([
        'variant' => 'warning',
        'title' => 'Updated',
    ]))
    ->withContent("Updated **Markdown** content.\n\n- Still nested Markdown\n- Still source-preserving");

$withUpdatedCallout = $document->withUpdatedSection($callout, $updatedCallout);
$withoutCta = $withUpdatedCallout->withoutSection($withUpdatedCallout->findByType('cta')[0]);

$newSection = new SectionDraft(
    type: 'product-card',
    attributes: SectionAttributes::fromArray([
        'sku' => 'markdown-sections',
        'url' => 'https://example.com/packages/nocturnal-markdown-sections',
    ]),
    content: "### Package\n\nA reusable Markdown section parser.",
);

$withProductBeforeCta = $document->withInsertedSectionBefore($cta, $newSection);

echo "Original document remains unchanged:" . PHP_EOL;
echo $document->toMarkdown() . PHP_EOL . PHP_EOL;

echo "Updated callout, CTA removed:" . PHP_EOL;
echo $withoutCta->toMarkdown() . PHP_EOL . PHP_EOL;

echo "Inserted product card before CTA:" . PHP_EOL;
echo $withProductBeforeCta->toMarkdown() . PHP_EOL . PHP_EOL;

echo "Standalone section draft Markdown:" . PHP_EOL;
echo $newSection->toMarkdown() . PHP_EOL;
