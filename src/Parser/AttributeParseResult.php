<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Parser;

use Nocturnal\MarkdownSections\Value\Diagnostic;
use Nocturnal\MarkdownSections\Value\SectionAttributes;

final readonly class AttributeParseResult
{
    /**
     * @param list<Diagnostic> $diagnostics
     */
    public function __construct(
        public SectionAttributes $attributes,
        public array $diagnostics = [],
    ) {
    }
}
