<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Parser;

final readonly class SourceLine
{
    public function __construct(
        public string $text,
        public string $ending,
        public int $startOffset,
        public int $contentEndOffset,
        public int $endOffset,
    ) {
    }
}
