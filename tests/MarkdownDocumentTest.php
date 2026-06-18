<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Tests;

use InvalidArgumentException;
use Nocturnal\MarkdownSections\MarkdownSections;
use Nocturnal\MarkdownSections\Value\SectionAttributes;
use PHPUnit\Framework\TestCase;

final class MarkdownDocumentTest extends TestCase
{
    public function testUpdatesSectionWithoutChangingOriginalDocument(): void
    {
        $document = MarkdownSections::parse("Before\n:::callout{variant=info}\nOld\n:::\nAfter");
        $section = $document->sections()[0];

        $updated = $document->withUpdatedSection(
            $section,
            $section->toDraft()
                ->withAttributes(SectionAttributes::fromArray(['variant' => 'warning']))
                ->withContent('New'),
        );

        self::assertSame("Before\n:::callout{variant=info}\nOld\n:::\nAfter", $document->toMarkdown());
        self::assertSame("Before\n:::callout{variant=\"warning\"}\nNew\n:::\nAfter", $updated->toMarkdown());
        self::assertSame('warning', $updated->sections()[0]->getAttribute('variant'));
    }

    public function testRemovesSectionAndPreservesSurroundingMarkdownExactly(): void
    {
        $document = MarkdownSections::parse("A\n\n:::faq\nQ\n:::\n\nB");
        $updated = $document->withoutSection($document->sections()[0]);

        self::assertSame("A\n\n\n\nB", $updated->toMarkdown());
        self::assertSame([], $updated->sections());
    }

    public function testRejectsSectionFromDifferentDocumentState(): void
    {
        $document = MarkdownSections::parse(":::callout\nOld\n:::");
        $section = $document->sections()[0];
        $updated = $document->withUpdatedSection($section, $section->toDraft()->withContent('New'));

        $this->expectException(InvalidArgumentException::class);

        $updated->withoutSection($section);
    }
}
