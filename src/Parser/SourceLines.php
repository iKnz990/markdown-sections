<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Parser;

final class SourceLines
{
    /**
     * @return list<SourceLine>
     */
    public static function split(string $markdown): array
    {
        $lines = [];
        $offset = 0;
        $length = strlen($markdown);

        while ($offset < $length) {
            $lineStart = $offset;
            $nextReturn = strpos($markdown, "\r", $offset);
            $nextNewline = strpos($markdown, "\n", $offset);
            $nextBreak = self::firstBreak($nextReturn, $nextNewline);

            if ($nextBreak === null) {
                $lines[] = new SourceLine(substr($markdown, $lineStart), '', $lineStart, $length, $length);
                break;
            }

            $text = substr($markdown, $lineStart, $nextBreak - $lineStart);
            $ending = $markdown[$nextBreak];
            $lineEnd = $nextBreak + 1;

            if ($ending === "\r" && ($markdown[$lineEnd] ?? '') === "\n") {
                $ending = "\r\n";
                $lineEnd++;
            }

            $lines[] = new SourceLine($text, $ending, $lineStart, $nextBreak, $lineEnd);
            $offset = $lineEnd;
        }

        return $lines;
    }

    private static function firstBreak(false|int $return, false|int $newline): ?int
    {
        if ($return === false && $newline === false) {
            return null;
        }

        if ($return === false) {
            return $newline;
        }

        if ($newline === false) {
            return $return;
        }

        return min($return, $newline);
    }
}
