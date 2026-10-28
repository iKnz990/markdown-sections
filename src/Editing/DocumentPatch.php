<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Editing;

use InvalidArgumentException;
use Nocturnal\MarkdownSections\MarkdownDocument;
use Nocturnal\MarkdownSections\MarkdownSections;
use Nocturnal\MarkdownSections\Parser\ParserLimits;

final readonly class DocumentPatch
{
    /**
     * @param list<PatchOperation> $operations
     */
    public function __construct(
        private string $originalMarkdown,
        public string $documentChecksum,
        private array $operations,
        private ?ParserLimits $limits = null,
    ) {
        if (PatchOperation::checksum($originalMarkdown) !== $documentChecksum) {
            throw new InvalidArgumentException('Patch checksum does not match its original document source.');
        }

        foreach ($operations as $operation) {
            if ($operation->span->endOffset > strlen($originalMarkdown)) {
                throw new InvalidArgumentException('Patch operation source span exceeds the document length.');
            }
        }

        $this->assertNonOverlapping($operations);
    }

    public static function fromOperation(string $originalMarkdown, PatchOperation $operation, ?ParserLimits $limits = null): self
    {
        return new self($originalMarkdown, PatchOperation::checksum($originalMarkdown), [$operation], $limits);
    }

    /**
     * @return list<PatchOperation>
     */
    public function operations(): array
    {
        return $this->operations;
    }

    public function isEmpty(): bool
    {
        return $this->operations === [];
    }

    public function applyTo(MarkdownDocument $document): MarkdownDocument
    {
        if ($document->checksum() !== $this->documentChecksum) {
            throw new InvalidArgumentException('Patch was created for a different document state.');
        }

        $updated = $document->toMarkdown();
        $operations = $this->operations;
        usort(
            $operations,
            static fn(PatchOperation $left, PatchOperation $right): int => ($right->span->startOffset <=> $left->span->startOffset)
                ?: ($right->span->endOffset <=> $left->span->endOffset)
        );

        foreach ($operations as $operation) {
            if (! $operation->expectedSourceMatches($updated)) {
                throw new InvalidArgumentException('Patch operation no longer matches its source span checksum.');
            }

            $updated = substr($updated, 0, $operation->span->startOffset)
                . $operation->replacement
                . substr($updated, $operation->span->endOffset);
        }

        return MarkdownSections::parse($updated, $document->parserLimits());
    }

    public function toMarkdown(): string
    {
        return $this->applyTo(MarkdownSections::parse($this->originalMarkdown, $this->limits))->toMarkdown();
    }

    public function toUnifiedDiff(string $from = 'original.md', string $to = 'updated.md'): string
    {
        $lines = ['--- ' . $from, '+++ ' . $to];

        foreach ($this->operations as $operation) {
            $oldLines = $this->previewLines($operation->originalSource($this->originalMarkdown));
            $newLines = $this->previewLines($operation->replacement);
            $label = $operation->label === '' ? $operation->kind : $operation->label;

            $lines[] = sprintf(
                '@@ -%d,%d +%d,%d @@ %s checksum:%s',
                $operation->span->startLine,
                count($oldLines),
                $operation->span->startLine,
                count($newLines),
                $label,
                substr($operation->expectedChecksum, 0, 12),
            );

            foreach ($oldLines as $line) {
                $lines[] = '-' . $line;
            }

            foreach ($newLines as $line) {
                $lines[] = '+' . $line;
            }
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param list<PatchOperation> $operations
     */
    private function assertNonOverlapping(array $operations): void
    {
        usort(
            $operations,
            static fn(PatchOperation $left, PatchOperation $right): int => ($left->span->startOffset <=> $right->span->startOffset)
                ?: ($left->span->endOffset <=> $right->span->endOffset)
        );

        $previousEnd = -1;
        $previousInsertion = -1;
        foreach ($operations as $operation) {
            if ($operation->span->startOffset < $previousEnd
                || ($operation->isInsertion() && $operation->span->startOffset === $previousInsertion)) {
                throw new InvalidArgumentException('Patch operations must not overlap.');
            }

            $previousEnd = $operation->span->endOffset;
            $previousInsertion = $operation->isInsertion() ? $operation->span->startOffset : -1;
        }
    }

    /**
     * @return list<string>
     */
    private function previewLines(string $source): array
    {
        if ($source === '') {
            return [];
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $source);
        $lines = explode("\n", $normalized);

        if (end($lines) === '') {
            array_pop($lines);
        }

        return $lines;
    }
}
