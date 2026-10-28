<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Parser;

use LogicException;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Parser\MarkdownParser;

/** Uses CommonMark source positions to exclude code examples, including list containers. */
final readonly class LiteralCodeLines
{
    /** @param array<int, true> $lines */
    private function __construct(private array $lines, public bool $hasUnclosedFence)
    {
    }

    public static function fromMarkdown(string $markdown): self
    {
        $environment = (new Environment([]))->addExtension(new CommonMarkCoreExtension());
        $walker = (new MarkdownParser($environment))->parse($markdown)->walker();
        $lines = [];
        $unclosed = false;

        while (($event = $walker->next()) !== null) {
            $node = $event->getNode();
            if (! $event->isEntering() || (! $node instanceof FencedCode && ! $node instanceof IndentedCode)) {
                continue;
            }

            $startLine = $node->getStartLine();
            $endLine = $node->getEndLine();
            if ($startLine === null || $endLine === null) {
                throw new LogicException('CommonMark did not provide source positions for a code block.');
            }
            for ($line = $startLine; $line <= $endLine; $line++) {
                $lines[$line] = true;
            }

            if ($node instanceof FencedCode) {
                // CommonMark excludes an explicit closer from the literal, but not a
                // final content line when a container ends or the input reaches EOF.
                if ($endLine - $startLine !== substr_count($node->getLiteral(), "\n") + 1) {
                    $unclosed = true;
                }
            }
        }

        return new self($lines, $unclosed);
    }

    public function contains(int $line): bool
    {
        return isset($this->lines[$line]);
    }
}
