<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Value;

final readonly class Diagnostic
{
    public function __construct(
        public string $code,
        public string $message,
        public int $line,
    ) {
    }

    public static function malformedAttribute(string $message, int $line): self
    {
        return new self('malformed_attribute', $message, $line);
    }

    public static function unclosedSection(string $type, int $line): self
    {
        return new self('unclosed_section', "Section \"$type\" is missing a closing fence.", $line);
    }

    public static function limitExceeded(string $message, int $line): self
    {
        return new self('limit_exceeded', $message, $line);
    }
}
