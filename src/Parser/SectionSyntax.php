<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Parser;

final class SectionSyntax
{
    private const TYPE_PATTERN = '[A-Za-z0-9_-]+';

    public static function matchOpening(string $trimmedLine): ?OpeningFence
    {
        if (! preg_match('/\A:::(' . self::TYPE_PATTERN . ')(?:\{(.*)\})?\z/', $trimmedLine, $matches)) {
            return null;
        }

        return new OpeningFence($matches[1], $matches[2] ?? '');
    }

    public static function isClosing(string $line): bool
    {
        return preg_match('/\A {0,3}:::[ \t]*\z/', $line) === 1;
    }

    public static function isValidType(string $type): bool
    {
        return preg_match('/\A' . self::TYPE_PATTERN . '\z/', $type) === 1;
    }
}
