<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections;

use InvalidArgumentException;
use Nocturnal\MarkdownSections\Value\Diagnostic;
use Nocturnal\MarkdownSections\Value\Section;
use Nocturnal\MarkdownSections\Value\SectionDraft;

final readonly class MarkdownDocument
{
    /**
     * @param list<Section> $sections
     * @param list<Diagnostic> $diagnostics
     */
    public function __construct(
        private string $markdown,
        private array $sections,
        private array $diagnostics = [],
    ) {
    }

    public function toMarkdown(): string
    {
        return $this->markdown;
    }

    /**
     * @return list<Section>
     */
    public function sections(): array
    {
        return $this->sections;
    }

    /**
     * @return list<Diagnostic>
     */
    public function diagnostics(): array
    {
        return $this->diagnostics;
    }

    /**
     * @return list<Section>
     */
    public function findByType(string $type): array
    {
        return array_values(array_filter(
            $this->sections,
            static fn(Section $section): bool => $section->type === $type
        ));
    }

    public function withUpdatedSection(Section $section, SectionDraft $draft): self
    {
        return $this->withReplacement($section, $draft->toMarkdown());
    }

    public function withoutSection(Section $section): self
    {
        return $this->withReplacement($section, '');
    }

    private function withReplacement(Section $section, string $replacement): self
    {
        $span = $section->span;
        $current = substr($this->markdown, $span->startOffset, $span->length());

        if ($current !== $section->rawBlock) {
            throw new InvalidArgumentException('The section does not match this document source.');
        }

        $updated = substr($this->markdown, 0, $span->startOffset)
            . $replacement
            . substr($this->markdown, $span->endOffset);

        return MarkdownSections::parse($updated);
    }
}
