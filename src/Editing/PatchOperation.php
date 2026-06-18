<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Editing;

use Nocturnal\MarkdownSections\Value\SourceSpan;

final readonly class PatchOperation
{
    private const HASH_ALGORITHM = 'sha256';

    private function __construct(
        public string $kind,
        public SourceSpan $span,
        public string $expectedChecksum,
        public string $replacement,
        public string $label = '',
    ) {
    }

    public static function replace(
        SourceSpan $span,
        string $expectedSource,
        string $replacement,
        string $label = '',
    ): self {
        return new self('replace', $span, self::checksum($expectedSource), $replacement, $label);
    }

    public static function insert(int $offset, int $line, string $markdown, string $label = ''): self
    {
        return new self(
            kind: 'insert',
            span: new SourceSpan($line, $line, $offset, $offset),
            expectedChecksum: self::checksum(''),
            replacement: $markdown,
            label: $label,
        );
    }

    public function isInsertion(): bool
    {
        return $this->span->length() === 0;
    }

    public function originalSource(string $markdown): string
    {
        return substr($markdown, $this->span->startOffset, $this->span->length());
    }

    public function expectedSourceMatches(string $markdown): bool
    {
        return self::checksum($this->originalSource($markdown)) === $this->expectedChecksum;
    }

    public static function checksum(string $source): string
    {
        return hash(self::HASH_ALGORITHM, $source);
    }
}
