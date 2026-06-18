<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Nocturnal\MarkdownSections\MarkdownSections;
use Nocturnal\MarkdownSections\Value\SectionAttributes;
use Nocturnal\MarkdownSections\Value\SectionDraft;

$markdown = <<<'MD'
# Launch Notes

:::cta{href="/demo" label="Book demo"}
Book a walkthrough.
:::

Keep this paragraph untouched.
MD;

$document = MarkdownSections::parse($markdown);
$cta = $document->findByType('cta')[0];
$faq = new SectionDraft(
    'faq',
    SectionAttributes::fromArray(['question' => 'Can an agent add sections?']),
    'Yes. The application chooses the insertion point and previews the patch.',
);

$insertPatch = $document->previewInsertSectionAfter($cta, $faq);
$withFaq = $document->applyPatch($insertPatch);

$note = new SectionDraft(
    'note',
    SectionAttributes::fromArray(['tone' => 'internal']),
    'This note was appended without reparsing the rest of the document.',
);

$withNote = $withFaq->withAppendedSection($note);

try {
    $withFaq->applyPatch($document->previewWithoutSection($cta));
} catch (\InvalidArgumentException $exception) {
    echo "Rejected stale patch: " . $exception->getMessage() . PHP_EOL . PHP_EOL;
}

echo "Insert patch:" . PHP_EOL;
echo $insertPatch->toUnifiedDiff('launch.before.md', 'launch.after.md') . PHP_EOL;

echo "Final Markdown:" . PHP_EOL;
echo $withNote->toMarkdown() . PHP_EOL;
