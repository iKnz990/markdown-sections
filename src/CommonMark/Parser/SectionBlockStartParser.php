<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\CommonMark\Parser;

use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;
use Nocturnal\MarkdownSections\CommonMark\Node\SectionNode;
use Nocturnal\MarkdownSections\Parser\AttributeParser;
use Nocturnal\MarkdownSections\Parser\SectionSyntax;

final class SectionBlockStartParser implements BlockStartParserInterface
{
    public function __construct(private readonly AttributeParser $attributeParser = new AttributeParser())
    {
    }

    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        for ($node = $parserState->getLastMatchedBlockParser()->getBlock(); $node !== null; $node = $node->parent()) {
            if ($node instanceof SectionNode) {
                return BlockStart::none();
            }
        }

        if ($cursor->isIndented() || $cursor->getNextNonSpaceCharacter() !== ':') {
            return BlockStart::none();
        }

        $opening = SectionSyntax::matchOpening(trim($cursor->getLine()));
        if ($opening === null) {
            return BlockStart::none();
        }

        $attributeResult = $this->attributeParser->parse($opening->attributes);
        $parser = new SectionBlockContinueParser(
            $opening->type,
            $attributeResult->attributes->all(),
            $cursor->getLine(),
        );

        $cursor->advanceToEnd();

        return BlockStart::of($parser)->at($cursor);
    }
}
