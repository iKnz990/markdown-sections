<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\CommonMark\Parser;

use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\BlockQuote;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Parser\Block\BlockQuoteParser;
use League\CommonMark\Extension\CommonMark\Parser\Block\ListItemParser;
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

        if (! $cursor->isIndented() && SectionSyntax::isClosing($line)
            && ! $this->fencedCodeContinues($cursor, $activeBlockParser)) {
            $this->block->markClosed();

            return BlockContinue::finished();
        }

        return BlockContinue::at($cursor);
    }

    public function closeBlock(): void
    {
        $this->block->setRawSource(implode("\n", $this->rawLines));
    }

    private function fencedCodeContinues(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): bool
    {
        if (! $activeBlockParser->getBlock() instanceof FencedCode) {
            return false;
        }

        $containers = [];
        for ($node = $activeBlockParser->getBlock()->parent(); $node !== $this->block; $node = $node->parent()) {
            if ($node === null) {
                return false;
            }
            $containers[] = $node;
        }

        $cursor = clone $cursor;
        foreach (array_reverse($containers) as $container) {
            // Probe fresh core parsers; the real descendants have not continued yet.
            $parser = match (true) {
                $container instanceof ListItem => new ListItemParser($container->getListData()),
                $container instanceof BlockQuote => new BlockQuoteParser(),
                default => null,
            };
            if ($parser === null) {
                continue;
            }

            $continuation = $parser->tryContinue(clone $cursor, $activeBlockParser);
            if ($continuation === null || $continuation->isFinalize()) {
                return false;
            }
            if (($state = $continuation->getCursorState()) !== null) {
                $cursor->restoreState($state);
            }
        }

        return true;
    }
}
