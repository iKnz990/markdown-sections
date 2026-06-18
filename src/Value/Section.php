<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Value;

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

    public function toDraft(): SectionDraft
    {
        return new SectionDraft($this->type, $this->attributes, $this->content);
    }
}
