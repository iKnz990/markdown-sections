<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections;

use InvalidArgumentException;
use Nocturnal\MarkdownSections\Editing\DocumentPatch;
use Nocturnal\MarkdownSections\Editing\PatchOperation;
use Nocturnal\MarkdownSections\Parser\ParserLimits;
use Nocturnal\MarkdownSections\Primitive\PrimitiveIssue;
use Nocturnal\MarkdownSections\Primitive\PrimitiveRegistry;
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
        private ?ParserLimits $limits = null,
    ) {
    }

    public function toMarkdown(): string
    {
        return $this->markdown;
    }

    public function checksum(): string
    {
        return PatchOperation::checksum($this->markdown);
    }

    public function parserLimits(): ParserLimits
    {
        return $this->limits ?? ParserLimits::defaults();
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
        return $this->previewUpdatedSection($section, $draft)->applyTo($this);
    }

    public function withoutSection(Section $section): self
    {
        return $this->previewWithoutSection($section)->applyTo($this);
    }

    public function withInsertedSectionBefore(Section $section, SectionDraft $draft): self
    {
        return $this->previewInsertSectionBefore($section, $draft)->applyTo($this);
    }

    public function withInsertedSectionAfter(Section $section, SectionDraft $draft): self
    {
        return $this->previewInsertSectionAfter($section, $draft)->applyTo($this);
    }

    public function withAppendedSection(SectionDraft $draft): self
    {
        return $this->previewAppendSection($draft)->applyTo($this);
    }

    public function previewUpdatedSection(Section $section, SectionDraft $draft): DocumentPatch
    {
        return $this->previewReplacement($section, $draft->toMarkdown($section->lineEnding()), 'update ' . $section->type);
    }

    public function previewWithoutSection(Section $section): DocumentPatch
    {
        return $this->previewReplacement($section, '', 'remove ' . $section->type);
    }

    public function previewInsertSectionBefore(Section $section, SectionDraft $draft): DocumentPatch
    {
        $this->assertSectionMatches($section);
        $lineEnding = $this->lineEndingForInsertion($section);
        $operation = PatchOperation::insert(
            $section->span->startOffset,
            $section->span->startLine,
            $draft->toMarkdown($lineEnding) . $lineEnding . $lineEnding,
            'insert before ' . $section->type,
        );

        return DocumentPatch::fromOperation($this->markdown, $operation, $this->parserLimits());
    }

    public function previewInsertSectionAfter(Section $section, SectionDraft $draft): DocumentPatch
    {
        $this->assertSectionMatches($section);
        $lineEnding = $this->lineEndingForInsertion($section);
        $offset = $this->offsetAfterSectionLine($section);
        $prefix = $offset === $section->span->endOffset ? $lineEnding : '';
        $operation = PatchOperation::insert(
            $offset,
            $section->span->endLine + 1,
            $prefix . $draft->toMarkdown($lineEnding) . $lineEnding . $lineEnding,
            'insert after ' . $section->type,
        );

        return DocumentPatch::fromOperation($this->markdown, $operation, $this->parserLimits());
    }

    public function previewAppendSection(SectionDraft $draft): DocumentPatch
    {
        $lineEnding = $this->documentLineEnding();
        $prefix = $this->appendPrefix($lineEnding);
        $operation = PatchOperation::insert(
            strlen($this->markdown),
            $this->lineCount() + 1,
            $prefix . $draft->toMarkdown($lineEnding),
            'append ' . $draft->type,
        );

        return DocumentPatch::fromOperation($this->markdown, $operation, $this->parserLimits());
    }

    public function applyPatch(DocumentPatch $patch): self
    {
        return $patch->applyTo($this);
    }

    /**
     * @return array{
     *     documentChecksum: string,
     *     sections: list<array{
     *         index: int,
     *         type: string,
     *         attributes: array<string, string>,
     *         content: string,
     *         source: array{startLine: int, endLine: int, startOffset: int, endOffset: int},
     *         checksum: string,
     *         valid: bool,
     *         issues: list<array{code: string, message: string, line: int}>
     *     }>
     * }
     */
    public function toEditManifest(?PrimitiveRegistry $registry = null): array
    {
        $validation = $registry?->validate($this);
        $sections = [];

        foreach ($this->sections as $section) {
            $issues = $validation?->issuesForSection($section->index) ?? [];
            $sections[] = [
                'index' => $section->index,
                'type' => $section->type,
                'attributes' => $section->attributes->all(),
                'content' => $section->content,
                'source' => $section->span->toArray(),
                'checksum' => $section->checksum(),
                'valid' => $issues === [],
                'issues' => array_map(
                    static fn(PrimitiveIssue $issue): array => [
                        'code' => $issue->code,
                        'message' => $issue->message,
                        'line' => $issue->line,
                    ],
                    $issues,
                ),
            ];
        }

        return [
            'documentChecksum' => $this->checksum(),
            'sections' => $sections,
        ];
    }

    private function previewReplacement(Section $section, string $replacement, string $label): DocumentPatch
    {
        $this->assertSectionMatches($section);

        return DocumentPatch::fromOperation(
            $this->markdown,
            PatchOperation::replace($section->span, $section->rawBlock, $replacement, $label),
            $this->parserLimits(),
        );
    }

    private function assertSectionMatches(Section $section): void
    {
        $current = substr($this->markdown, $section->span->startOffset, $section->span->length());

        if ($current !== $section->rawBlock) {
            throw new InvalidArgumentException('The section does not match this document source.');
        }
    }

    private function lineEndingForInsertion(Section $section): string
    {
        return $section->lineEnding() ?: $this->documentLineEnding();
    }

    private function documentLineEnding(): string
    {
        if (preg_match('/\r\n|\n|\r/', $this->markdown, $matches) === 1) {
            return $matches[0];
        }

        return "\n";
    }

    private function offsetAfterSectionLine(Section $section): int
    {
        $offset = $section->span->endOffset;
        $nextTwo = substr($this->markdown, $offset, 2);

        if ($nextTwo === "\r\n") {
            return $offset + 2;
        }

        $next = $this->markdown[$offset] ?? '';
        if ($next === "\n" || $next === "\r") {
            return $offset + 1;
        }

        return $offset;
    }

    private function appendPrefix(string $lineEnding): string
    {
        if ($this->markdown === '') {
            return '';
        }

        if (str_ends_with($this->markdown, "\r\n") || str_ends_with($this->markdown, "\n") || str_ends_with($this->markdown, "\r")) {
            return $lineEnding;
        }

        return $lineEnding . $lineEnding;
    }

    private function lineCount(): int
    {
        if ($this->markdown === '') {
            return 0;
        }

        return substr_count(str_replace(["\r\n", "\r"], "\n", $this->markdown), "\n") + 1;
    }
}
