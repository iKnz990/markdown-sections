<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Tests;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Nocturnal\MarkdownSections\CommonMark\MarkdownSectionsExtension;
use Nocturnal\MarkdownSections\Rendering\GenericSectionRenderer;
use Nocturnal\MarkdownSections\Rendering\SectionRenderContext;
use Nocturnal\MarkdownSections\Rendering\SectionRendererInterface;
use Nocturnal\MarkdownSections\Rendering\SectionRendererRegistry;
use PHPUnit\Framework\TestCase;

final class CommonMarkExtensionTest extends TestCase
{
    public function testRegisteredRendererReceivesAttributesAndRenderedChildMarkdown(): void
    {
        $renderer = new class implements SectionRendererInterface {
            public function render(SectionRenderContext $context): string
            {
                return sprintf(
                    '<aside data-variant="%s">%s</aside>',
                    htmlspecialchars($context->attributes->get('variant'), ENT_QUOTES, 'UTF-8'),
                    $context->innerHtml,
                );
            }
        };
        $registry = (new SectionRendererRegistry())->register('callout', $renderer);
        $html = $this->convert(":::callout{variant=info}\n**Bold**\n:::", $registry);

        self::assertStringContainsString('<aside data-variant="info">', $html);
        self::assertStringContainsString('<strong>Bold</strong>', $html);
    }

    public function testUnknownSectionsUseSafeEscapedRawSourceFallback(): void
    {
        $html = $this->convert(":::unknown\n<script>alert(1)</script>\n:::");

        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function testGenericFallbackRendersSemanticSectionWithAttributes(): void
    {
        $registry = (new SectionRendererRegistry())->withFallback(new GenericSectionRenderer());
        $html = $this->convert(":::feature{title=\"Hello\"}\nBody\n:::", $registry);

        self::assertStringContainsString('<section data-markdown-section="feature"', $html);
        self::assertStringContainsString('data-markdown-section-title="Hello"', $html);
        self::assertStringContainsString('<p>Body</p>', $html);
    }

    public function testNonSectionColonRunsRemainNormalMarkdown(): void
    {
        $html = $this->convert("::::callout\nNot a section\n::::");

        self::assertStringContainsString('<p>::::callout', $html);
        self::assertStringContainsString('Not a section', $html);
    }

    private function convert(string $markdown, ?SectionRendererRegistry $registry = null): string
    {
        $environment = new Environment([]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new MarkdownSectionsExtension($registry));

        return (string) (new MarkdownConverter($environment))->convert($markdown);
    }
}
