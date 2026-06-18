<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Parser;

use Nocturnal\MarkdownSections\Value\Diagnostic;
use Nocturnal\MarkdownSections\Value\SectionAttributes;

final class AttributeParser
{
    public function parse(string $source, int $line = 0): AttributeParseResult
    {
        $attributes = [];
        /** @var list<Diagnostic> $diagnostics */
        $diagnostics = [];
        $position = 0;
        $length = strlen($source);

        while ($position < $length) {
            $this->skipWhitespace($source, $position, $length);
            if ($position >= $length) {
                break;
            }

            if (! preg_match('/\G[A-Za-z_][A-Za-z0-9_-]*/A', $source, $matches, 0, $position)) {
                $diagnostics[] = Diagnostic::malformedAttribute('Expected an attribute name.', $line);
                $this->skipToken($source, $position, $length);
                continue;
            }

            $key = $matches[0];
            $position += strlen($key);
            $this->skipWhitespace($source, $position, $length);

            if (($source[$position] ?? '') !== '=') {
                $diagnostics[] = Diagnostic::malformedAttribute("Attribute \"$key\" is missing '='.", $line);
                $this->skipToken($source, $position, $length);
                continue;
            }

            $position++;
            $this->skipWhitespace($source, $position, $length);
            $value = $this->readValue($source, $position, $length, $diagnostics, $line, $key);

            if ($value !== null) {
                $attributes[$key] = $value;
            }
        }

        return new AttributeParseResult(SectionAttributes::fromArray($attributes), $diagnostics);
    }

    /**
     * @param list<Diagnostic> $diagnostics
     */
    private function readValue(
        string $source,
        int &$position,
        int $length,
        array &$diagnostics,
        int $line,
        string $key,
    ): ?string {
        if ($position >= $length) {
            $diagnostics[] = Diagnostic::malformedAttribute("Attribute \"$key\" is missing a value.", $line);
            return null;
        }

        if ($source[$position] === '"') {
            return $this->readQuotedValue($source, $position, $length, $diagnostics, $line, $key);
        }

        $start = $position;
        while ($position < $length && ! ctype_space($source[$position])) {
            $position++;
        }

        if ($start === $position) {
            $diagnostics[] = Diagnostic::malformedAttribute("Attribute \"$key\" is missing a value.", $line);
            return null;
        }

        return substr($source, $start, $position - $start);
    }

    /**
     * @param list<Diagnostic> $diagnostics
     */
    private function readQuotedValue(
        string $source,
        int &$position,
        int $length,
        array &$diagnostics,
        int $line,
        string $key,
    ): ?string {
        $position++;
        $value = '';

        while ($position < $length) {
            $char = $source[$position];
            if ($char === '"') {
                $position++;
                return $value;
            }

            if ($char === '\\' && $position + 1 < $length) {
                $value .= $source[$position + 1];
                $position += 2;
                continue;
            }

            $value .= $char;
            $position++;
        }

        $diagnostics[] = Diagnostic::malformedAttribute("Attribute \"$key\" has an unclosed quoted value.", $line);
        return null;
    }

    private function skipWhitespace(string $source, int &$position, int $length): void
    {
        while ($position < $length && ctype_space($source[$position])) {
            $position++;
        }
    }

    private function skipToken(string $source, int &$position, int $length): void
    {
        while ($position < $length && ! ctype_space($source[$position])) {
            $position++;
        }
    }
}
