<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections;

use Nocturnal\MarkdownSections\Parser\MarkdownSectionParser;
use Nocturnal\MarkdownSections\Parser\ParserLimits;

final class MarkdownSections
{
    public static function parse(string $markdown, ?ParserLimits $limits = null): MarkdownDocument
    {
        return (new MarkdownSectionParser($limits ?? ParserLimits::defaults()))->parse($markdown);
    }
}
