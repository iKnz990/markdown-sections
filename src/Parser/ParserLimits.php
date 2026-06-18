<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Parser;

final readonly class ParserLimits
{
    public const DEFAULT_MAX_CONTENT_SIZE = 1_048_576;
    public const DEFAULT_MAX_SECTIONS = 100;
    public const DEFAULT_MAX_SECTION_SIZE = 102_400;

    public function __construct(
        public int $maxContentSize,
        public int $maxSections,
        public int $maxSectionSize,
    ) {
    }

    public static function defaults(): self
    {
        return new self(
            self::DEFAULT_MAX_CONTENT_SIZE,
            self::DEFAULT_MAX_SECTIONS,
            self::DEFAULT_MAX_SECTION_SIZE,
        );
    }
}
