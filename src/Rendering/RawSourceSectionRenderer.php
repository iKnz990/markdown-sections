<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Rendering;

final class RawSourceSectionRenderer implements SectionRendererInterface
{
    public function render(SectionRenderContext $context): string
    {
        return htmlspecialchars($context->rawSource ?? '', ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
