<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Tests;

use Nocturnal\MarkdownSections\MarkdownSections;
use Nocturnal\MarkdownSections\Parser\ParserLimits;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MalformedRecoveryRegressionTest extends TestCase
{
    /** @return iterable<string, array{int, string}> */
    public static function unclosedRegions(): iterable
    {
        yield '25 LF openers' => [25, "\n"];
        yield '25 CRLF openers' => [25, "\r\n"];
        yield '100 LF openers' => [100, "\n"];
        yield '200 LF openers' => [200, "\n"];
    }

    #[DataProvider('unclosedRegions')]
    public function testUnclosedRegionIsDiagnosedOnlyOnce(int $openers, string $ending): void
    {
        $source = str_repeat(':::a' . $ending, $openers);
        $document = MarkdownSections::parse($source, new ParserLimits(1_048_576, 1, 102_400));
        $diagnostics = $document->diagnostics();

        self::assertSame([], $document->sections());
        self::assertSame($source, $document->toMarkdown());
        self::assertCount($openers, $diagnostics);
        self::assertSame(array_fill(0, $openers - 1, 'nested_section'), array_column(array_slice($diagnostics, 0, -1), 'code'));
        self::assertSame(range(2, $openers), array_column(array_slice($diagnostics, 0, -1), 'line'));
        self::assertSame('unclosed_section', $diagnostics[$openers - 1]->code);
        self::assertSame(1, $diagnostics[$openers - 1]->line);
    }

    public function testUnclosedRegionKeepsTheValidSectionBeforeIt(): void
    {
        $source = ":::before\nKeep this.\n:::\n\n" . str_repeat(":::a\n", 25);
        $document = MarkdownSections::parse($source);

        self::assertCount(1, $document->sections());
        self::assertSame('before', $document->sections()[0]->type);
        self::assertSame('Keep this.', $document->sections()[0]->content);
        self::assertCount(25, $document->diagnostics());
        self::assertSame(5, $document->diagnostics()[24]->line);
        self::assertSame('unclosed_section', $document->diagnostics()[24]->code);
        self::assertSame($source, $document->toMarkdown());
    }

    public function testOversizedRegionDoesNotExposeNestedSectionsAndKeepsTheFollowingSection(): void
    {
        $source = ":::oversized\nToo large.\n:::nested\nok\n:::\n\n:::after\nok\n:::\n";
        $document = MarkdownSections::parse($source, new ParserLimits(1_048_576, 100, 4));

        self::assertCount(1, $document->sections());
        self::assertSame('after', $document->sections()[0]->type);
        self::assertSame('ok', $document->sections()[0]->content);
        self::assertSame(7, $document->sections()[0]->span->startLine);
        self::assertSame(['nested_section', 'limit_exceeded'], array_column($document->diagnostics(), 'code'));
        self::assertSame([3, 1], array_column($document->diagnostics(), 'line'));
        self::assertSame($source, $document->toMarkdown());
    }
}
