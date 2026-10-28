<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Tests;

use InvalidArgumentException;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Nocturnal\MarkdownSections\CommonMark\MarkdownSectionsExtension;
use Nocturnal\MarkdownSections\MarkdownSections;
use Nocturnal\MarkdownSections\Primitive\PrimitiveDefinition;
use Nocturnal\MarkdownSections\Rendering\GenericSectionRenderer;
use Nocturnal\MarkdownSections\Rendering\SectionRendererRegistry;
use Nocturnal\MarkdownSections\Value\SectionAttributes;
use Nocturnal\MarkdownSections\Value\SectionDraft;
use PHPUnit\Framework\TestCase;

final class FlatGrammarRegressionTest extends TestCase
{
    public function testDraftRejectsNestedOpeningFenceOutsideCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SectionDraft('cta', SectionAttributes::fromArray([]), ":::unknown\nBody");
    }

    public function testExistingNestedOpeningIsDiagnosedAndRenderedAsLiteralContent(): void
    {
        $source = ":::cta\n:::unknown\nBody\n:::";
        $document = MarkdownSections::parse($source);

        self::assertCount(1, $document->sections());
        self::assertSame('nested_section', $document->diagnostics()[0]->code);
        self::assertSame(2, $document->diagnostics()[0]->line);

        $html = $this->convert($source);
        self::assertSame(1, substr_count($html, '<section '));
        self::assertStringNotContainsString('data-markdown-section="unknown"', $html);
        self::assertStringContainsString(':::unknown', $html);
    }

    public function testNestedOpeningInsideFencedCodeRemainsAllowedLiteralContent(): void
    {
        $content = "```markdown\n:::unknown\nBody\n```";
        $draft = new SectionDraft('cta', SectionAttributes::fromArray([]), $content);
        $document = MarkdownSections::parse($draft->toMarkdown());

        self::assertSame([], $document->diagnostics());
        self::assertSame($content, $document->sections()[0]->content);
        self::assertSame(1, substr_count($this->convert($draft->toMarkdown()), '<section '));
    }

    public function testAttributeNameRejectsTrailingNewline(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SectionAttributes::fromArray(["title\n" => 'value']);
    }

    public function testDraftTypeRejectsTrailingNewline(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SectionDraft("cta\n", SectionAttributes::fromArray([]), 'Body');
    }

    public function testPrimitiveAttributeDefinitionRejectsTrailingNewline(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PrimitiveDefinition::type('cta')->requireAttributes("title\n");
    }

    public function testDraftMayContainClosingMarkerInListCode(): void
    {
        $content = "- ```text\n  :::\n  ```";
        $draft = new SectionDraft('example', SectionAttributes::fromArray([]), $content);
        $document = MarkdownSections::parse($draft->toMarkdown());

        self::assertSame($content, $document->sections()[0]->content);
        self::assertSame([], $document->diagnostics());
    }

    public function testDraftRejectsUnclosedListCodeFence(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SectionDraft('example', SectionAttributes::fromArray([]), "- ```text\n  Body");
    }

    private function convert(string $source): string
    {
        $registry = (new SectionRendererRegistry())->withFallback(new GenericSectionRenderer());
        $environment = new Environment([]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new MarkdownSectionsExtension($registry));

        return (string) (new MarkdownConverter($environment))->convert($source);
    }
}
