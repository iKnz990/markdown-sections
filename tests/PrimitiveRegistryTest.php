<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Tests;

use Nocturnal\MarkdownSections\MarkdownSections;
use Nocturnal\MarkdownSections\Primitive\PrimitiveDefinition;
use Nocturnal\MarkdownSections\Primitive\PrimitiveRegistry;
use PHPUnit\Framework\TestCase;

final class PrimitiveRegistryTest extends TestCase
{
    public function testValidatesRequiredUnknownAndContentRules(): void
    {
        $document = MarkdownSections::parse(":::cta{href=\"/buy\" extra=yes}\n\n:::");
        $registry = (new PrimitiveRegistry())->register(
            PrimitiveDefinition::type('cta')
                ->requireAttributes('href', 'label')
                ->optionalAttributes('variant')
                ->rejectUnknownAttributes()
                ->requireContent(),
        );

        $result = $registry->validate($document);
        $codes = array_map(static fn($issue): string => $issue->code, $result->issues());

        self::assertFalse($result->isValid());
        self::assertSame(['missing_attribute', 'unknown_attribute', 'empty_content'], $codes);
    }

    public function testCanReportUnknownPrimitivesWhenStrict(): void
    {
        $document = MarkdownSections::parse(":::unknown\nBody\n:::");
        $result = (new PrimitiveRegistry())->validate($document, reportUnknown: true);

        self::assertFalse($result->isValid());
        self::assertSame('unknown_primitive', $result->issues()[0]->code);
    }

    public function testEditManifestIncludesChecksumsSourceSpansAndValidationState(): void
    {
        $markdown = ":::cta{href=\"/buy\" label=\"Buy\"}\nGo\n:::";
        $document = MarkdownSections::parse($markdown);
        $section = $document->sections()[0];
        $registry = (new PrimitiveRegistry())->register(
            PrimitiveDefinition::type('cta')
                ->requireAttributes('href', 'label')
                ->rejectUnknownAttributes()
                ->requireContent(),
        );

        $manifest = $document->toEditManifest($registry);

        self::assertSame(hash('sha256', $markdown), $manifest['documentChecksum']);
        self::assertSame('cta', $manifest['sections'][0]['type']);
        self::assertSame($section->checksum(), $manifest['sections'][0]['checksum']);
        self::assertSame($section->span->startOffset, $manifest['sections'][0]['source']['startOffset']);
        self::assertTrue($manifest['sections'][0]['valid']);
        self::assertSame([], $manifest['sections'][0]['issues']);
    }
}
