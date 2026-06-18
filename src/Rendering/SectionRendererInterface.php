<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Rendering;

interface SectionRendererInterface
{
    public function render(SectionRenderContext $context): string;
}
