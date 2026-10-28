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
        $sourceLines = SourceLines::split($markdown);
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
                $lastLine = $sourceLines[$endLine - 1]->text ?? '';
                $closing = (string) preg_replace('/\A[ \t]*(?:>[ \t]*)*/', '', $lastLine);
                $pattern = '/\A' . preg_quote($node->getChar(), '/') . '{' . $node->getLength() . ',}[ \t]*\z/';
                if ($startLine === $endLine || preg_match($pattern, $closing) !== 1) {
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
