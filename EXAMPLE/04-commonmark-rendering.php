<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Nocturnal\MarkdownSections\CommonMark\MarkdownSectionsExtension;
use Nocturnal\MarkdownSections\Rendering\GenericSectionRenderer;
use Nocturnal\MarkdownSections\Rendering\RawSourceSectionRenderer;
use Nocturnal\MarkdownSections\Rendering\SectionRenderContext;
use Nocturnal\MarkdownSections\Rendering\SectionRendererInterface;
use Nocturnal\MarkdownSections\Rendering\SectionRendererRegistry;

$markdown = <<<'MD'
# Render Demo

:::callout{variant=info title="Renderer registry"}
This content is **normal Markdown** before it reaches the custom renderer.
:::

:::unknown{unsafe="<script>"}
<script>alert("escaped")</script>
:::

:::product-card{sku=parser}
### Product Card

Generic fallback keeps nested Markdown as rendered HTML.
:::
MD;

$calloutRenderer = new class implements SectionRendererInterface {
    public function render(SectionRenderContext $context): string
    {
        $variant = htmlspecialchars($context->attributes->get('variant', 'info'), ENT_QUOTES, 'UTF-8');
        $title = htmlspecialchars($context->attributes->get('title', 'Note'), ENT_QUOTES, 'UTF-8');
        $lineRange = $context->startLine . '-' . $context->endLine;

        return sprintf(
            '<aside class="callout callout-%s" data-source-lines="%s"><h2>%s</h2>%s</aside>',
            $variant,
            htmlspecialchars($lineRange, ENT_QUOTES, 'UTF-8'),
            $title,
            $context->innerHtml,
        );
    }
};

$registry = (new SectionRendererRegistry())
    ->register('callout', $calloutRenderer)
    ->register('unknown', new RawSourceSectionRenderer())
    ->withFallback(new GenericSectionRenderer());

$environment = new Environment([]);
$environment->addExtension(new CommonMarkCoreExtension());
$environment->addExtension(new MarkdownSectionsExtension($registry));

$converter = new MarkdownConverter($environment);

echo $converter->convert($markdown);
