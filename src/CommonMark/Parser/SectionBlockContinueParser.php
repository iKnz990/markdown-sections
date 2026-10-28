<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\CommonMark\Parser;

use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Cursor;
use Nocturnal\MarkdownSections\CommonMark\Node\SectionNode;
use Nocturnal\MarkdownSections\Parser\SectionSyntax;

final class SectionBlockContinueParser extends AbstractBlockContinueParser
{
    private SectionNode $block;

    /**
     * @var list<string>
     */
    private array $rawLines;

    /**
     * @param array<string, string> $attributes
     */
    public function __construct(string $type, array $attributes, string $openingLine)
    {
        $this->block = new SectionNode($type, $attributes, $openingLine);
        $this->rawLines = [$openingLine];
    }

    public function getBlock(): SectionNode
    {
        return $this->block;
    }

    public function isContainer(): bool
    {
        return true;
    }

    public function canContain(AbstractBlock $childBlock): bool
    {
        unset($childBlock);

        return true;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): BlockContinue
    {
        $line = $cursor->getLine();
        $this->rawLines[] = $line;

        if (! $cursor->isIndented() && ! $activeBlockParser->getBlock() instanceof FencedCode && SectionSyntax::isClosing($line)) {
            $this->block->markClosed();

            return BlockContinue::finished();
        }

        return BlockContinue::at($cursor);
    }

    public function closeBlock(): void
    {
        $this->block->setRawSource(implode("\n", $this->rawLines));
    }
}
