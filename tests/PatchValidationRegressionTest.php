<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Tests;

use InvalidArgumentException;
use Nocturnal\MarkdownSections\Editing\DocumentPatch;
use Nocturnal\MarkdownSections\Editing\PatchOperation;
use Nocturnal\MarkdownSections\MarkdownSections;
use Nocturnal\MarkdownSections\Value\SectionAttributes;
use Nocturnal\MarkdownSections\Value\SectionDraft;
use Nocturnal\MarkdownSections\Value\SourceSpan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PatchValidationRegressionTest extends TestCase
{
    /** @return iterable<string, array{int, int, int, int}> */
    public static function invalidSpans(): iterable
    {
        yield 'negative offset' => [1, 1, -1, 1];
        yield 'reversed offsets' => [1, 1, 2, 1];
        yield 'zero line' => [0, 1, 0, 1];
        yield 'reversed lines' => [2, 1, 0, 1];
    }

    #[DataProvider('invalidSpans')]
    public function testInvalidSourceSpansAreRejected(int $startLine, int $endLine, int $start, int $end): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SourceSpan($startLine, $endLine, $start, $end);
    }

    public function testOutOfBoundsInsertionIsRejectedInsteadOfSilentlyAppending(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DocumentPatch::fromOperation('abc', PatchOperation::insert(1000, 1, 'X'))->toMarkdown();
    }

    public function testOutOfBoundsReplacementIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $operation = PatchOperation::replace(new SourceSpan(1, 1, 2, 100), 'c', 'X');
        DocumentPatch::fromOperation('abc', $operation)->toMarkdown();
    }

    public function testInsertionExactlyAtEndOfFileRemainsValid(): void
    {
        self::assertSame('abcX', DocumentPatch::fromOperation('abc', PatchOperation::insert(3, 1, 'X'))->toMarkdown());
    }

    public function testDraftRejectsClosingFenceThatWouldEscapeItsSection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SectionDraft('cta', SectionAttributes::fromArray([]), "Body\n:::\nUnbounded prose");
    }

    public function testDraftMayContainLiteralClosingFenceInsideFencedCode(): void
    {
        $draft = new SectionDraft('example', SectionAttributes::fromArray([]), "```text\n:::\n```");
        $document = MarkdownSections::parse($draft->toMarkdown());

        self::assertCount(1, $document->sections());
        self::assertSame($draft->content, $document->sections()[0]->content);
    }

    public function testDraftRejectsUnclosedCodeFenceThatWouldHideItsClosingFence(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SectionDraft('example', SectionAttributes::fromArray([]), "```text\nBody");
    }

    public function testPatchCannotBindAnotherDocumentChecksumToItsPreview(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DocumentPatch('abc', hash('sha256', 'xyz'), []);
    }

    public function testReplacementAndInsertionAtTheSameBoundaryHaveDeterministicOrder(): void
    {
        $replace = PatchOperation::replace(new SourceSpan(1, 1, 0, 1), 'a', 'A');
        $insert = PatchOperation::insert(0, 1, 'X');

        self::assertSame('XAbc', (new DocumentPatch('abc', hash('sha256', 'abc'), [$replace, $insert]))->toMarkdown());
        self::assertSame('XAbc', (new DocumentPatch('abc', hash('sha256', 'abc'), [$insert, $replace]))->toMarkdown());
    }

    public function testAmbiguousDuplicateInsertionOffsetsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DocumentPatch('abc', hash('sha256', 'abc'), [PatchOperation::insert(0, 1, 'X'), PatchOperation::insert(0, 1, 'Y')]);
    }

    /** @return iterable<string, array<array<string, string>>> */
    public static function invalidAttributes(): iterable
    {
        yield 'invalid name' => [['bad name' => 'value']];
        yield 'newline in name' => [["title\n:::" => 'value']];
        yield 'newline in value' => [['title' => "one\ntwo"]];
        yield 'carriage return in value' => [['title' => "one\rtwo"]];
    }

    /** @param array<string, string> $attributes */
    #[DataProvider('invalidAttributes')]
    public function testAttributesCannotBreakTheSingleLineOpeningFence(array $attributes): void
    {
        $this->expectException(InvalidArgumentException::class);
        SectionAttributes::fromArray($attributes);
    }
}
