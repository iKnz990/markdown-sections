<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Value;

use InvalidArgumentException;
use Nocturnal\MarkdownSections\Parser\SectionSyntax;

final readonly class SectionDraft
{
    public function __construct(
        public string $type,
        public SectionAttributes $attributes,
        public string $content,
    ) {
        if (! SectionSyntax::isValidType($type)) {
            throw new InvalidArgumentException("Invalid section type \"$type\".");
        }
    }

    public function withType(string $type): self
    {
        return new self($type, $this->attributes, $this->content);
    }

    public function withAttributes(SectionAttributes $attributes): self
    {
        return new self($this->type, $attributes, $this->content);
    }

    public function withContent(string $content): self
    {
        return new self($this->type, $this->attributes, $content);
    }

    public function toMarkdown(string $lineEnding = "\n"): string
    {
        $opening = ':::' . $this->type . $this->attributes->toMarkdown();
        $content = str_replace(["\r\n", "\r"], "\n", $this->content);
        $content = str_replace("\n", $lineEnding, $content);

        if ($content === '') {
            return $opening . $lineEnding . ':::';
        }

        if (str_ends_with($content, $lineEnding)) {
            return $opening . $lineEnding . $content . ':::';
        }

        return $opening . $lineEnding . $content . $lineEnding . ':::';
    }
}
