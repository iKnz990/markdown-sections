<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Value;

use Nocturnal\MarkdownSections\Editing\PatchOperation;

final readonly class Section
{
    public function __construct(
        public int $index,
        public string $type,
        public SectionAttributes $attributes,
        public string $content,
        public SourceSpan $span,
        public string $rawBlock,
    ) {
    }

    public function getAttribute(string $key, string $default = ''): string
    {
        return $this->attributes->get($key, $default);
    }

    public function hasAttribute(string $key): bool
    {
        return $this->attributes->has($key);
    }

    public function checksum(): string
    {
        return PatchOperation::checksum($this->rawBlock);
    }

    public function lineEnding(): string
    {
        if (str_contains($this->rawBlock, "\r\n")) {
            return "\r\n";
        }

        if (str_contains($this->rawBlock, "\r")) {
            return "\r";
        }

        return "\n";
    }

    public function toDraft(): SectionDraft
    {
        return new SectionDraft($this->type, $this->attributes, $this->content);
    }
}
