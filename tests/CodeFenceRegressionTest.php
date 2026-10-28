<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Tests;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Nocturnal\MarkdownSections\CommonMark\MarkdownSectionsExtension;
use Nocturnal\MarkdownSections\MarkdownSections;
use Nocturnal\MarkdownSections\Rendering\GenericSectionRenderer;
use Nocturnal\MarkdownSections\Rendering\SectionRendererRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CodeFenceRegressionTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function codeExamples(): iterable
    {
        yield 'backticks' => ["```markdown\n:::cta\nExample\n:::\n```\n"];
        yield 'tildes' => ["~~~markdown\n:::cta\nExample\n:::\n~~~\n"];
        yield 'four backticks with shorter inner fence' => ["````markdown\n```\n:::cta\nExample\n:::\n````\n"];
        yield 'indented spaces' => ["    :::cta\n    Example\n    :::\n"];
        yield 'indented tab' => ["\t:::cta\n\tExample\n\t:::\n"];
        yield 'unclosed fence' => ["```markdown\n:::cta\nExample\n:::\n"];
        yield 'unordered list backticks' => ["- ```markdown\n  :::cta\n  Example\n  :::\n  ```\n"];
        yield 'ordered list tildes' => ["1. ~~~markdown\n   :::cta\n   Example\n   :::\n   ~~~\n"];
        yield 'nested list backticks' => ["- Outer\n  - ```markdown\n    :::cta\n    Example\n    :::\n    ```\n"];
    }

    #[DataProvider('codeExamples')]
    public function testCodeExamplesAreNeverExposedAsEditableSections(string $markdown): void
    {
        $document = MarkdownSections::parse($markdown);

        self::assertSame([], $document->sections());
        self::assertSame($markdown, $document->toMarkdown());
    }

    public function testCodeClosingMarkerInsideSectionIsLiteralContent(): void
    {
        $markdown = ":::example\n```text\n:::\n```\nAfter code\n:::\n";
        $section = MarkdownSections::parse($markdown)->sections()[0];

        self::assertSame("```text\n:::\n```\nAfter code", $section->content);
        self::assertSame(6, $section->span->endLine);
        self::assertSame([], MarkdownSections::parse($markdown)->diagnostics());
    }

    public function testCommonMarkAlsoKeepsClosingMarkerInsideFencedCode(): void
    {
        $registry = (new SectionRendererRegistry())->withFallback(new GenericSectionRenderer());
        $environment = new Environment([]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new MarkdownSectionsExtension($registry));

        $html = (string) (new MarkdownConverter($environment))->convert(":::example\n```text\n:::\n```\nAfter code\n:::\n");

        self::assertStringContainsString("<code class=\"language-text\">:::\n</code>", $html);
        self::assertStringContainsString('<p>After code</p></section>', $html);
        self::assertSame(1, substr_count($html, '<section '));
    }

    public function testClosingMarkerInsideListCodeDoesNotCloseItsContainingSection(): void
    {
        $source = ":::example\n- ```text\n  :::\n  ```\nAfter code\n:::\n";
        $document = MarkdownSections::parse($source);

        self::assertCount(1, $document->sections());
        self::assertSame("- ```text\n  :::\n  ```\nAfter code", $document->sections()[0]->content);
        self::assertSame([], $document->diagnostics());
    }

    public function testIndentedClosingMarkerRemainsInsideTheRenderedSection(): void
    {
        $source = ":::example\n\n    :::\n    literal code\n\nAfter\n:::\n";
        $document = MarkdownSections::parse($source);
        self::assertSame(7, $document->sections()[0]->span->endLine);

        $registry = (new SectionRendererRegistry())->withFallback(new GenericSectionRenderer());
        $environment = new Environment([]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new MarkdownSectionsExtension($registry));
        $html = (string) (new MarkdownConverter($environment))->convert($source);

        self::assertStringContainsString("<pre><code>:::\nliteral code\n</code></pre>", $html);
        self::assertStringContainsString('<p>After</p></section>', $html);
    }
}
