<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Parser;

final readonly class OpeningFence
{
    public function __construct(
        public string $type,
        public string $attributes,
    ) {
    }
}
