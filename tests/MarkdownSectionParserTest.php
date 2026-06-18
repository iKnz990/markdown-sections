<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Tests;

use InvalidArgumentException;
use Nocturnal\MarkdownSections\MarkdownSections;
use Nocturnal\MarkdownSections\Parser\ParserLimits;
use PHPUnit\Framework\TestCase;

final class MarkdownSectionParserTest extends TestCase
{
    public function testEmptyAndPlainMarkdownHaveNoSections(): void
    {
        self::assertSame([], MarkdownSections::parse('')->sections());
        self::assertSame([], MarkdownSections::parse("# Title\n\nPlain text.")->sections());
    }

    public function testParsesSimpleSectionWithPositionAndRawBlock(): void
    {
        $markdown = "# Intro\n\n:::callout\nContent\n:::\nAfter";
        $document = MarkdownSections::parse($markdown);
        $section = $document->sections()[0];

        self::assertSame('callout', $section->type);
        self::assertSame('Content', $section->content);
        self::assertSame(3, $section->span->startLine);
        self::assertSame(5, $section->span->endLine);
        self::assertSame(':::callout' . "\n" . 'Content' . "\n" . ':::', $section->rawBlock);
        self::assertSame($section->rawBlock, substr($markdown, $section->span->startOffset, $section->span->length()));
    }

    public function testParsesQuotedUnquotedEscapedAndDuplicateAttributes(): void
    {
        $markdown = <<<'MD'
:::cta{url="/contact" buttonText="Get \"Started\"" variant=primary variant=secondary}
Ready.
:::
MD;

        $section = MarkdownSections::parse($markdown)->sections()[0];

        self::assertSame('/contact', $section->getAttribute('url'));
        self::assertSame('Get "Started"', $section->getAttribute('buttonText'));
        self::assertSame('secondary', $section->getAttribute('variant'));
    }

    public function testParsesMultipleSectionsAndFindsByType(): void
    {
        $markdown = <<<'MD'
:::callout{variant=info}
One
:::

:::faq{title="FAQ Section"}
Two
:::

:::callout
Three
:::
MD;

        $document = MarkdownSections::parse($markdown);

        self::assertCount(3, $document->sections());
        self::assertCount(2, $document->findByType('callout'));
        self::assertSame('faq', $document->sections()[1]->type);
    }

    public function testPreservesNestedMarkdownAsSectionContent(): void
    {
        $markdown = <<<'MD'
:::code{lang=php filename="example.php"}
## Heading
- Item 1
- Item 2

```php
echo "Hello";
```
:::
MD;

        $section = MarkdownSections::parse($markdown)->sections()[0];

        self::assertSame('code', $section->type);
        self::assertStringContainsString('## Heading', $section->content);
        self::assertStringContainsString('```php', $section->content);
        self::assertSame('example.php', $section->getAttribute('filename'));
    }

    public function testHandlesWindowsLineEndingsAndEmptyContent(): void
    {
        $windows = ":::callout\r\nContent here\r\n:::";
        $empty = ":::callout{variant=info}\n:::";

        $windowsSection = MarkdownSections::parse($windows)->sections()[0];
        $emptySection = MarkdownSections::parse($empty)->sections()[0];

        self::assertSame(":::callout\r\nContent here\r\n:::", $windowsSection->rawBlock);
        self::assertSame('Content here', $windowsSection->content);
        self::assertSame('', $emptySection->content);
    }

    public function testIgnoresUnclosedSectionsAndExposesDiagnostic(): void
    {
        $document = MarkdownSections::parse(":::callout\nNo close");

        self::assertSame([], $document->sections());
        self::assertSame('unclosed_section', $document->diagnostics()[0]->code);
    }

    public function testMalformedAttributesExposeDiagnosticsButKeepValidAttributes(): void
    {
        $markdown = <<<'MD'
:::callout{title "Oops" variant=info}
Content
:::
MD;

        $document = MarkdownSections::parse($markdown);

        self::assertSame('info', $document->sections()[0]->getAttribute('variant'));
        self::assertNotSame([], $document->diagnostics());
        self::assertSame('malformed_attribute', $document->diagnostics()[0]->code);
    }

    public function testKebabCaseTypesAreSupported(): void
    {
        $section = MarkdownSections::parse(":::product-card\nBody\n:::")->sections()[0];

        self::assertSame('product-card', $section->type);
    }

    public function testContentLimitThrowsForOversizedDocument(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MarkdownSections::parse('123456', new ParserLimits(5, 100, 100));
    }
}
