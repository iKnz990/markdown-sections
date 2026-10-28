<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Value;

use InvalidArgumentException;

final readonly class SourceSpan
{
    public function __construct(
        public int $startLine,
        public int $endLine,
        public int $startOffset,
        public int $endOffset,
    ) {
        if ($startLine < 1 || $endLine < $startLine || $startOffset < 0 || $endOffset < $startOffset) {
            throw new InvalidArgumentException('Source spans require positive ordered lines and non-negative ordered offsets.');
        }
    }

    public function length(): int
    {
        return $this->endOffset - $this->startOffset;
    }

    /**
     * @return array{startLine: int, endLine: int, startOffset: int, endOffset: int}
     */
    public function toArray(): array
    {
        return [
            'startLine' => $this->startLine,
            'endLine' => $this->endLine,
            'startOffset' => $this->startOffset,
            'endOffset' => $this->endOffset,
        ];
    }
}
