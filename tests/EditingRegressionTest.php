<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Tests;

use InvalidArgumentException;
use Nocturnal\MarkdownSections\MarkdownSections;
use Nocturnal\MarkdownSections\Parser\ParserLimits;
use Nocturnal\MarkdownSections\Value\Section;
use Nocturnal\MarkdownSections\Value\SectionAttributes;
use Nocturnal\MarkdownSections\Value\SectionDraft;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EditingRegressionTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function lineEndings(): iterable
    {
        yield 'LF' => ["\n"];
        yield 'CRLF' => ["\r\n"];
        yield 'CR' => ["\r"];
    }

    #[DataProvider('lineEndings')]
    public function testInsertAfterAtEndOfFileKeepsBothSectionFences(string $ending): void
    {
        $source = ":::cta{$ending}Original{$ending}:::";
        $document = MarkdownSections::parse($source);
        $draft = new SectionDraft('faq', SectionAttributes::fromArray([]), 'Answer');

        $updated = $document->withInsertedSectionAfter($document->sections()[0], $draft);

        self::assertSame($source . $ending . $draft->toMarkdown($ending) . $ending . $ending, $updated->toMarkdown());
        self::assertSame(['cta', 'faq'], array_map(static fn(Section $section): string => $section->type, $updated->sections()));
        self::assertSame($source, $document->toMarkdown());
    }

    public function testInsertBeforeRejectsAnAnchorWhoseSourceChanged(): void
    {
        $original = MarkdownSections::parse(":::cta\nOld\n:::");
        $current = MarkdownSections::parse(":::cta\nNew\n:::");

        $this->expectException(InvalidArgumentException::class);
        $current->previewInsertSectionBefore($original->sections()[0], $this->draft());
    }

    public function testInsertAfterRejectsAnAnchorWhoseSourceChanged(): void
    {
        $original = MarkdownSections::parse(":::cta\nOld\n:::");
        $current = MarkdownSections::parse("Preface\n:::cta\nOld\n:::");

        $this->expectException(InvalidArgumentException::class);
        $current->previewInsertSectionAfter($original->sections()[0], $this->draft());
    }

    public function testPatchRetainsCustomMaximumSectionCount(): void
    {
        $document = MarkdownSections::parse(":::cta\nx\n:::", new ParserLimits(500, 1, 100));

        $updated = $document->withAppendedSection($this->draft());

        self::assertCount(1, $updated->sections());
        self::assertSame('limit_exceeded', $updated->diagnostics()[0]->code);
    }

    public function testPatchRetainsCustomMaximumDocumentSize(): void
    {
        $document = MarkdownSections::parse(":::cta\nx\n:::", new ParserLimits(20, 10, 100));

        $this->expectException(InvalidArgumentException::class);
        $document->withAppendedSection($this->draft());
    }

    public function testPatchRetainsCustomMaximumSectionSize(): void
    {
        $document = MarkdownSections::parse(":::cta\nx\n:::", new ParserLimits(500, 10, 4));

        $updated = $document->withUpdatedSection($document->sections()[0], $this->draft());

        self::assertSame([], $updated->sections());
        self::assertSame('limit_exceeded', $updated->diagnostics()[0]->code);
    }

    public function testPatchPreviewRetainsCustomMaximumDocumentSize(): void
    {
        $document = MarkdownSections::parse(":::cta\nx\n:::", new ParserLimits(20, 10, 100));

        $this->expectException(InvalidArgumentException::class);
        $document->previewAppendSection($this->draft())->toMarkdown();
    }

    public function testPatchPreviewSupportsExplicitlyRaisedDocumentLimit(): void
    {
        $prefix = str_repeat('x', ParserLimits::DEFAULT_MAX_CONTENT_SIZE) . "\n";
        $document = MarkdownSections::parse($prefix . ":::cta\nx\n:::", new ParserLimits(2_000_000, 10, 100));

        $patch = $document->previewUpdatedSection($document->sections()[0], $this->draft());

        self::assertSame($prefix . $this->draft()->toMarkdown(), $patch->toMarkdown());
    }

    private function draft(): SectionDraft
    {
        return new SectionDraft('faq', SectionAttributes::fromArray([]), 'Answer');
    }
}
