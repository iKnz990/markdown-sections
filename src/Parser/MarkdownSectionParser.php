<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Parser;

use InvalidArgumentException;
use Nocturnal\MarkdownSections\MarkdownDocument;
use Nocturnal\MarkdownSections\Value\Diagnostic;
use Nocturnal\MarkdownSections\Value\Section;
use Nocturnal\MarkdownSections\Value\SourceSpan;

final class MarkdownSectionParser
{
    private AttributeParser $attributeParser;

    public function __construct(private readonly ParserLimits $limits)
    {
        $this->attributeParser = new AttributeParser();
    }

    public function parse(string $markdown): MarkdownDocument
    {
        $size = strlen($markdown);
        if ($size > $this->limits->maxContentSize) {
            throw new InvalidArgumentException(sprintf(
                'Markdown content exceeds maximum size of %d bytes (got %d bytes).',
                $this->limits->maxContentSize,
                $size,
            ));
        }

        $lines = SourceLines::split($markdown);
        $literalCode = LiteralCodeLines::fromMarkdown($markdown);
        $sections = [];
        $diagnostics = [];
        $line = 0;
        $sectionCount = 0;
        $code = new FencedCodeTracker();

        while ($line < count($lines)) {
            if ($literalCode->contains($line + 1) || $code->consume($lines[$line]->text)
                || preg_match('/^(?: {4}| {0,3}\t)/', $lines[$line]->text) === 1) {
                $line++;
                continue;
            }

            $opening = SectionSyntax::matchOpening(trim($lines[$line]->text));
            if ($opening === null) {
                $line++;
                continue;
            }

            if ($sectionCount >= $this->limits->maxSections) {
                $diagnostics[] = Diagnostic::limitExceeded('Maximum section count reached.', $line + 1);
                break;
            }

            $parsed = $this->parseSection($markdown, $lines, $line, $opening, $sectionCount, $literalCode);
            array_push($diagnostics, ...$parsed['diagnostics']);

            if ($parsed['section'] instanceof Section) {
                $sections[] = $parsed['section'];
                $sectionCount++;
                $line = $parsed['endIndex'] + 1;
                continue;
            }

            $line++;
        }

        return new MarkdownDocument($markdown, $sections, $diagnostics, $this->limits);
    }

    /**
     * @param list<SourceLine> $lines
     * @return array{section: Section|null, diagnostics: list<Diagnostic>, endIndex: int}
     */
    private function parseSection(
        string $markdown,
        array $lines,
        int $startIndex,
        OpeningFence $opening,
        int $sectionIndex,
        LiteralCodeLines $literalCode,
    ): array {
        $syntaxDiagnostics = [];
        $endIndex = $this->findClosingFence($lines, $startIndex + 1, $syntaxDiagnostics, $literalCode);
        if ($endIndex === null) {
            return [
                'section' => null,
                'diagnostics' => [...$syntaxDiagnostics, Diagnostic::unclosedSection($opening->type, $startIndex + 1)],
                'endIndex' => $startIndex,
            ];
        }

        $sectionSize = $this->sectionContentSize($lines, $startIndex + 1, $endIndex);
        if ($sectionSize > $this->limits->maxSectionSize) {
            return [
                'section' => null,
                'diagnostics' => [...$syntaxDiagnostics, Diagnostic::limitExceeded('Section content exceeds maximum size.', $startIndex + 1)],
                'endIndex' => $endIndex,
            ];
        }

        $attributeResult = $this->attributeParser->parse($opening->attributes, $startIndex + 1);
        $span = new SourceSpan(
            startLine: $startIndex + 1,
            endLine: $endIndex + 1,
            startOffset: $lines[$startIndex]->startOffset,
            endOffset: $lines[$endIndex]->contentEndOffset,
        );

        $section = new Section(
            index: $sectionIndex,
            type: $opening->type,
            attributes: $attributeResult->attributes,
            content: $this->contentBetween($lines, $startIndex + 1, $endIndex),
            span: $span,
            rawBlock: substr($markdown, $span->startOffset, $span->length()),
        );

        return [
            'section' => $section,
            'diagnostics' => [...$syntaxDiagnostics, ...$attributeResult->diagnostics],
            'endIndex' => $endIndex,
        ];
    }

    /**
     * @param list<SourceLine> $lines
     * @param list<Diagnostic> $diagnostics
     */
    private function findClosingFence(array $lines, int $startIndex, array &$diagnostics, LiteralCodeLines $literalCode): ?int
    {
        $code = new FencedCodeTracker();
        for ($index = $startIndex; $index < count($lines); $index++) {
            if ($literalCode->contains($index + 1) || $code->consume($lines[$index]->text)) {
                continue;
            }

            if (SectionSyntax::isClosing($lines[$index]->text)) {
                return $index;
            }
            if (preg_match('/\A {0,3}:::/', $lines[$index]->text) === 1
                && SectionSyntax::matchOpening(trim($lines[$index]->text)) !== null) {
                $diagnostics[] = new Diagnostic('nested_section', 'Nested section fences are unsupported and remain literal content.', $index + 1);
            }
        }

        return null;
    }

    /**
     * @param list<SourceLine> $lines
     */
    private function sectionContentSize(array $lines, int $startIndex, int $endIndex): int
    {
        $size = 0;
        for ($index = $startIndex; $index < $endIndex; $index++) {
            $size += strlen($lines[$index]->text) + strlen($lines[$index]->ending);
        }

        return $size;
    }

    /**
     * @param list<SourceLine> $lines
     */
    private function contentBetween(array $lines, int $startIndex, int $endIndex): string
    {
        $content = [];
        for ($index = $startIndex; $index < $endIndex; $index++) {
            $content[] = $lines[$index]->text;
        }

        return implode("\n", $content);
    }
}
