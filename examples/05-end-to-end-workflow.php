<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Nocturnal\MarkdownSections\CommonMark\MarkdownSectionsExtension;
use Nocturnal\MarkdownSections\MarkdownSections;
use Nocturnal\MarkdownSections\Rendering\GenericSectionRenderer;
use Nocturnal\MarkdownSections\Rendering\SectionRendererRegistry;
use Nocturnal\MarkdownSections\Value\SectionAttributes;

$markdown = <<<'MD'
# Editorial Brief

:::callout{variant=info title="Draft"}
This section starts as a draft.
:::

:::takeaways{style=bullet}
- Keep the parser small.
- Preserve user-authored Markdown.
- Render through CommonMark when HTML is needed.
:::
MD;

$document = MarkdownSections::parse($markdown);
$draftCallout = $document->findByType('callout')[0];

$published = $document->withUpdatedSection(
    $draftCallout,
    $draftCallout->toDraft()
        ->withAttributes(SectionAttributes::fromArray([
            'variant' => 'success',
            'title' => 'Published',
        ]))
        ->withContent('This section has been reviewed and approved.'),
);

$registry = (new SectionRendererRegistry())->withFallback(new GenericSectionRenderer());
$environment = new Environment([]);
$environment->addExtension(new CommonMarkCoreExtension());
$environment->addExtension(new MarkdownSectionsExtension($registry));

$converter = new MarkdownConverter($environment);

echo "Updated Markdown:" . PHP_EOL;
echo $published->toMarkdown() . PHP_EOL . PHP_EOL;

echo "Rendered HTML:" . PHP_EOL;
echo $converter->convert($published->toMarkdown());
