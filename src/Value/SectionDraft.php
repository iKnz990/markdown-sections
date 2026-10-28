<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Value;

use InvalidArgumentException;
use Nocturnal\MarkdownSections\Parser\FencedCodeTracker;
use Nocturnal\MarkdownSections\Parser\LiteralCodeLines;
use Nocturnal\MarkdownSections\Parser\SourceLines;
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

        $code = new FencedCodeTracker();
        $literalCode = LiteralCodeLines::fromMarkdown($content);
        foreach (SourceLines::split($content) as $index => $line) {
            if ($literalCode->contains($index + 1) || $code->consume($line->text)) {
                continue;
            }
            if (SectionSyntax::isClosing($line->text)) {
                throw new InvalidArgumentException('Section content cannot contain a closing section fence outside fenced code.');
            }
            if (preg_match('/\A {0,3}:::/', $line->text) === 1 && SectionSyntax::matchOpening(trim($line->text)) !== null) {
                throw new InvalidArgumentException('Section content cannot contain nested section opening fences outside fenced code.');
            }
        }
        if ($code->isOpen() || $literalCode->hasUnclosedFence) {
            throw new InvalidArgumentException('Section content must close its fenced code blocks.');
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
