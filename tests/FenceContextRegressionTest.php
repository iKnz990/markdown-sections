<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Tests;

use InvalidArgumentException;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Nocturnal\MarkdownSections\CommonMark\MarkdownSectionsExtension;
use Nocturnal\MarkdownSections\MarkdownSections;
use Nocturnal\MarkdownSections\Value\SectionAttributes;
use Nocturnal\MarkdownSections\Value\SectionDraft;
use Nocturnal\MarkdownSections\Rendering\GenericSectionRenderer;
use Nocturnal\MarkdownSections\Rendering\SectionRendererRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FenceContextRegressionTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function outdentedCodeBodies(): iterable
    {
        foreach (['~~~', '```'] as $fence) {
            yield $fence . ' unordered list' => ["- {$fence}\n  sample"];
            yield $fence . ' ordered list' => ["1. {$fence}\n   sample"];
            yield $fence . ' nested list' => ["- outer\n  - {$fence}\n    sample"];
            yield $fence . ' block quote' => ["> {$fence}\n> sample"];
            yield $fence . ' quote inside list' => ["- > {$fence}\n  > sample"];
        }
    }

    #[DataProvider('outdentedCodeBodies')]
    public function testSectionClosesWhenFencedCodeContainersDoNotContinue(string $body): void
    {
        $source = ":::example\n{$body}\n:::\nAfter";
        $document = MarkdownSections::parse($source);

        self::assertCount(1, $document->sections());
        self::assertSame($body, $document->sections()[0]->content);
        self::assertSame([], $document->diagnostics());
        self::assertMatchesRegularExpression('~</section>\s*<p>After</p>~', $this->render($source));
    }

    /** @return iterable<string, array{string}> */
    public static function literalSectionMarkers(): iterable
    {
        foreach (['~~~', '```'] as $fence) {
            yield $fence . ' direct' => ["{$fence}\n:::\nsample\n{$fence}"];
            yield $fence . ' unordered list' => ["- {$fence}\n  :::\n  sample\n  {$fence}"];
            yield $fence . ' ordered list' => ["1. {$fence}\n   :::\n   sample\n   {$fence}"];
            yield $fence . ' nested list' => ["- outer\n  - {$fence}\n    :::\n    sample\n    {$fence}"];
            yield $fence . ' block quote' => ["> {$fence}\n> :::\n> sample\n> {$fence}"];
            yield $fence . ' list inside quote' => ["> - {$fence}\n>   :::\n>   sample\n>   {$fence}"];
        }
    }

    #[DataProvider('literalSectionMarkers')]
    public function testSectionMarkersRemainLiteralWhenCodeContainersContinue(string $body): void
    {
        $source = ":::example\n{$body}\n:::\nAfter";
        $document = MarkdownSections::parse($source);
        $html = $this->render($source);

        self::assertCount(1, $document->sections());
        self::assertSame($body, $document->sections()[0]->content);
        self::assertStringContainsString(':::', $html);
        self::assertMatchesRegularExpression('~</section>\s*<p>After</p>~', $html);
    }

    /** @return iterable<string, array{string}> */
    public static function falseClosingCodeFences(): iterable
    {
        foreach (['~~~', '```'] as $fence) {
            yield $fence . ' four spaces' => ["{$fence}text\n    {$fence}"];
            yield $fence . ' tab' => ["{$fence}text\n\t{$fence}"];
            yield $fence . ' list relative four spaces' => ["- {$fence}text\n      {$fence}"];
            yield $fence . ' ordered list relative four spaces' => ["1. {$fence}text\n       {$fence}"];
            yield $fence . ' quote relative four spaces' => ["> {$fence}text\n>     {$fence}"];
            yield $fence . ' missing quote continuation' => ["> {$fence}text\n{$fence}"];
        }
    }

    #[DataProvider('falseClosingCodeFences')]
    public function testDraftRejectsFencesThatCommonMarkDidNotExplicitlyClose(string $content): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SectionDraft('example', SectionAttributes::fromArray([]), $content);
    }

    /** @return iterable<string, array{string}> */
    public static function balancedCodeFences(): iterable
    {
        foreach (['~~~', '```'] as $fence) {
            yield $fence . ' empty' => ["{$fence}\n{$fence}"];
            yield $fence . ' blank content' => ["{$fence}\n\n{$fence}"];
            yield $fence . ' three spaces' => ["{$fence}text\n   {$fence}"];
            yield $fence . ' list relative three spaces' => ["- {$fence}text\n     {$fence}"];
            yield $fence . ' ordered list relative three spaces' => ["1. {$fence}text\n      {$fence}"];
            yield $fence . ' quote relative three spaces' => ["> {$fence}text\n>    {$fence}"];
            yield $fence . ' nested list' => ["- outer\n  - {$fence}\n    sample\n    {$fence}"];
            yield $fence . ' CRLF' => ["{$fence}\r\nsample\r\n{$fence}"];
            yield $fence . ' CR' => ["{$fence}\rsample\r{$fence}"];
        }
    }

    #[DataProvider('balancedCodeFences')]
    public function testBalancedDraftCodeFencesSurviveRoundTrip(string $content): void
    {
        $draft = new SectionDraft('example', SectionAttributes::fromArray([]), $content);
        $document = MarkdownSections::parse($draft->toMarkdown());

        self::assertCount(1, $document->sections());
        self::assertSame(str_replace(["\r\n", "\r"], "\n", $content), $document->sections()[0]->content);
        self::assertSame([], $document->diagnostics());
    }

    public function testManyLiteralMarkersDoNotChangeTheSectionBoundary(): void
    {
        $body = "- ~~~\n" . str_repeat("  :::\n", 1000) . '  ~~~';
        $source = ":::example\n{$body}\n:::\nAfter";
        $html = $this->render($source);

        self::assertSame($body, MarkdownSections::parse($source)->sections()[0]->content);
        self::assertSame(1000, substr_count($html, ':::'));
        self::assertMatchesRegularExpression('~</section>\s*<p>After</p>~', $html);
    }

    private function render(string $source): string
    {
        $registry = (new SectionRendererRegistry())->withFallback(new GenericSectionRenderer());
        $environment = new Environment([]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new MarkdownSectionsExtension($registry));

        return (string) (new MarkdownConverter($environment))->convert($source);
    }
}
