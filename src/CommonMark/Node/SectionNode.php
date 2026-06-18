<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\CommonMark\Node;

use League\CommonMark\Node\Block\AbstractBlock;

final class SectionNode extends AbstractBlock
{
    private bool $closed = false;
    private string $rawSource = '';

    /**
     * @param array<string, string> $attributes
     */
    public function __construct(
        private readonly string $type,
        private readonly array $attributes,
        private readonly string $openingLine,
    ) {
        parent::__construct();
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array<string, string>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getOpeningLine(): string
    {
        return $this->openingLine;
    }

    public function markClosed(): void
    {
        $this->closed = true;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function setRawSource(string $rawSource): void
    {
        $this->rawSource = $rawSource;
    }

    public function getRawSource(): string
    {
        return $this->rawSource;
    }
}
