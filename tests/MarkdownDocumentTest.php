<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Tests;

use InvalidArgumentException;
use Nocturnal\MarkdownSections\MarkdownSections;
use Nocturnal\MarkdownSections\Value\SectionAttributes;
use Nocturnal\MarkdownSections\Value\SectionDraft;
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

    public function testPatchPreviewShowsReplacementAndAppliesToMatchingDocument(): void
    {
        $document = MarkdownSections::parse("Intro\n:::cta{label=Old}\nOld\n:::\nOutro");
        $section = $document->sections()[0];
        $patch = $document->previewUpdatedSection(
            $section,
            $section->toDraft()
                ->withAttributes(SectionAttributes::fromArray(['label' => 'New']))
                ->withContent('New'),
        );

        $diff = $patch->toUnifiedDiff();
        $updated = $document->applyPatch($patch);

        self::assertSame(hash('sha256', $document->toMarkdown()), $document->checksum());
        self::assertSame(hash('sha256', $section->rawBlock), $section->checksum());
        self::assertStringContainsString('@@ -2,3 +2,3 @@ update cta checksum:', $diff);
        self::assertStringContainsString('-Old', $diff);
        self::assertStringContainsString('+New', $diff);
        self::assertSame("Intro\n:::cta{label=\"New\"}\nNew\n:::\nOutro", $updated->toMarkdown());
    }

    public function testPatchRejectsStaleDocumentChecksum(): void
    {
        $document = MarkdownSections::parse(":::cta\nOld\n:::");
        $section = $document->sections()[0];
        $patch = $document->previewUpdatedSection($section, $section->toDraft()->withContent('New'));
        $stale = MarkdownSections::parse("Preface\n" . $document->toMarkdown());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('different document state');

        $stale->applyPatch($patch);
    }

    public function testInsertBeforeAfterAndAppendAreImmutable(): void
    {
        $document = MarkdownSections::parse("Top\n:::cta{label=Buy}\nOld\n:::\nBottom");
        $cta = $document->sections()[0];
        $faq = new SectionDraft(
            'faq',
            SectionAttributes::fromArray(['title' => 'Question']),
            'Answer',
        );

        $before = $document->withInsertedSectionBefore($cta, $faq);
        $after = $document->withInsertedSectionAfter($cta, $faq);
        $append = $document->withAppendedSection($faq);

        self::assertSame("Top\n:::cta{label=Buy}\nOld\n:::\nBottom", $document->toMarkdown());
        self::assertSame("Top\n:::faq{title=\"Question\"}\nAnswer\n:::\n\n:::cta{label=Buy}\nOld\n:::\nBottom", $before->toMarkdown());
        self::assertSame("Top\n:::cta{label=Buy}\nOld\n:::\n:::faq{title=\"Question\"}\nAnswer\n:::\n\nBottom", $after->toMarkdown());
        self::assertSame("Top\n:::cta{label=Buy}\nOld\n:::\nBottom\n\n:::faq{title=\"Question\"}\nAnswer\n:::", $append->toMarkdown());
    }

    public function testUpdatedSectionKeepsOriginalSectionLineEnding(): void
    {
        $document = MarkdownSections::parse(":::callout\r\nOld\r\n:::");
        $section = $document->sections()[0];

        $updated = $document->withUpdatedSection($section, $section->toDraft()->withContent('New'));

        self::assertSame(":::callout\r\nNew\r\n:::", $updated->toMarkdown());
    }
}
