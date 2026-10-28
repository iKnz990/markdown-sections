<?php

declare(strict_types=1);

if ($argc !== 2 || ! is_file($argv[1])) {
    fwrite(STDERR, "Usage: php tools/check-consumer.php <consumer/vendor/autoload.php>\n");
    exit(2);
}

require $argv[1];

use Nocturnal\MarkdownSections\MarkdownSections;
use Nocturnal\MarkdownSections\Primitive\PrimitiveDefinition;
use Nocturnal\MarkdownSections\Primitive\PrimitiveRegistry;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Nocturnal\MarkdownSections\CommonMark\MarkdownSectionsExtension;
use Nocturnal\MarkdownSections\Rendering\GenericSectionRenderer;
use Nocturnal\MarkdownSections\Rendering\SectionRendererRegistry;

$prefix = "# Consumer installation\r\n\r\n";
$suffix = "\r\n\r\nUntouched suffix.\r\n";
$source = $prefix . ":::note\r\nOriginal.\r\n:::" . $suffix;
$document = MarkdownSections::parse($source);
$registry = (new PrimitiveRegistry())->register(PrimitiveDefinition::type('note')->requireContent());
$section = $document->findByType('note')[0] ?? null;
if ($section === null || ! $registry->validate($document)->isValid()) {
    throw new RuntimeException('Consumer could not parse and validate the original document.');
}

$patch = $document->previewUpdatedSection($section, $section->toDraft()->withContent('Updated.'));
$updated = $document->applyPatch($patch);
if (! $registry->validate($updated)->isValid()
    || $updated->toMarkdown() !== $prefix . ":::note\r\nUpdated.\r\n:::" . $suffix
    || $document->toMarkdown() !== $source) {
    throw new RuntimeException('Consumer source preservation or immutability check failed.');
}

$renderers = (new SectionRendererRegistry())->withFallback(new GenericSectionRenderer());
$environment = new Environment(['html_input' => 'strip', 'allow_unsafe_links' => false]);
$environment->addExtension(new CommonMarkCoreExtension());
$environment->addExtension(new MarkdownSectionsExtension($renderers));
$html = (string) (new MarkdownConverter($environment))->convert($updated->toMarkdown());
if (! str_contains($html, '<section data-markdown-section="note">') || ! str_contains($html, '<p>Updated.</p>')) {
    throw new RuntimeException('Consumer CommonMark rendering check failed.');
}

try {
    $updated->applyPatch($patch);
} catch (InvalidArgumentException) {
    echo "Consumer install: parse, validate, preview, patch, render, CRLF preservation and stale rejection passed.\n";
    exit(0);
}

throw new RuntimeException('Consumer accepted a stale patch.');
