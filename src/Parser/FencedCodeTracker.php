<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Parser;

/** Tracks literal backtick and tilde code fences in the line-based section grammar. */
final class FencedCodeTracker
{
    private string $character = '';
    private int $length = 0;

    public function isOpen(): bool
    {
        return $this->length > 0;
    }

    /** Returns true for a fence delimiter or a line inside its literal contents. */
    public function consume(string $line): bool
    {
        if ($this->length > 0) {
            $pattern = '/^ {0,3}' . preg_quote($this->character, '/') . '{' . $this->length . ',}[ \t]*$/';
            if (preg_match($pattern, $line) === 1) {
                $this->length = 0;
                $this->character = '';
            }

            return true;
        }

        if (preg_match('/^ {0,3}(`{3,}|~{3,})(.*)$/', $line, $matches) !== 1) {
            return false;
        }

        if ($matches[1][0] === '`' && str_contains($matches[2], '`')) {
            return false;
        }

        $this->character = $matches[1][0];
        $this->length = strlen($matches[1]);

        return true;
    }
}
