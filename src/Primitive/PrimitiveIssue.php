<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Primitive;

final readonly class PrimitiveIssue
{
    public function __construct(
        public string $code,
        public string $message,
        public string $type,
        public int $sectionIndex,
        public int $line,
    ) {
    }
}
