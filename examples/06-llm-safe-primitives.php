<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Nocturnal\MarkdownSections\MarkdownSections;
use Nocturnal\MarkdownSections\Primitive\PrimitiveDefinition;
use Nocturnal\MarkdownSections\Primitive\PrimitiveRegistry;
use Nocturnal\MarkdownSections\Value\SectionAttributes;

$markdown = <<<'MD'
# Campaign Page

Opening copy stays exactly as written.

:::cta{href="/demo" label="Old label" variant=primary}
Old CTA body.
:::

Closing copy stays exactly as written.
MD;

$registry = (new PrimitiveRegistry())->register(
    PrimitiveDefinition::type('cta')
        ->requireAttributes('href', 'label')
        ->optionalAttributes('variant')
        ->rejectUnknownAttributes()
        ->requireContent(),
);

$document = MarkdownSections::parse($markdown);
$manifest = $document->toEditManifest($registry);
$cta = $document->findByType('cta')[0];

$patch = $document->previewUpdatedSection(
    $cta,
    $cta->toDraft()
        ->withAttributes(SectionAttributes::fromArray([
            'href' => '/strategy',
            'label' => 'Plan the repair',
            'variant' => 'primary',
        ]))
        ->withContent('Bring one broken workflow. Leave with the fix mapped.'),
);

echo "Manifest for agent:" . PHP_EOL;
echo json_encode([
    'documentChecksum' => $manifest['documentChecksum'],
    'sections' => $manifest['sections'],
], JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;

echo "Patch preview:" . PHP_EOL;
echo $patch->toUnifiedDiff('campaign.before.md', 'campaign.after.md') . PHP_EOL;

echo "Updated Markdown:" . PHP_EOL;
echo $document->applyPatch($patch)->toMarkdown() . PHP_EOL;
