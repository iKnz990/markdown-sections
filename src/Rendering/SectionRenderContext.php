<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Rendering;

use Nocturnal\MarkdownSections\Value\SectionAttributes;

final readonly class SectionRenderContext
{
    public function __construct(
        public string $type,
        public SectionAttributes $attributes,
        public string $innerHtml,
        public ?int $startLine = null,
        public ?int $endLine = null,
        public ?string $rawSource = null,
    ) {
    }
}
