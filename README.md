# nocturnal/markdown-sections

`nocturnal/markdown-sections` gives PHP applications a bounded editing surface inside Markdown.

It parses custom fenced sections, exposes them as immutable primitives with source spans and checksums, previews exact patches, and rewrites only the selected section. League CommonMark rendering is included, but rendering is the secondary path.

```markdown
Before this stays byte-for-byte.

:::cta{href="/demo" label="Book demo"}
Old call to action.
:::

After this stays byte-for-byte.
```

An editor, CMS task, migration, or LLM agent can update only the `:::cta` block and leave the rest of the file untouched.

## Install

```bash
composer require nocturnal/markdown-sections
```

For local development:

```bash
composer install
composer qa
```

## What Makes It Different

Most Markdown packages optimize for Markdown-to-HTML rendering. This package optimizes for safe source edits:

- parse named primitives such as `:::cta`, `:::faq`, `:::steps`, or `:::product-card`
- inspect source line numbers, byte offsets, and SHA-256 checksums
- validate primitives against project-defined contracts
- preview patches before applying them
- reject stale edits when the document checksum changed
- update, remove, insert, or append sections without serializing the whole Markdown AST
- render through League CommonMark when HTML is actually needed

The key contract: untouched Markdown remains untouched.

## Quick Edit

```php
use Nocturnal\MarkdownSections\MarkdownSections;
use Nocturnal\MarkdownSections\Value\SectionAttributes;

$document = MarkdownSections::parse($markdown);
$cta = $document->findByType('cta')[0];

$patch = $document->previewUpdatedSection(
    $cta,
    $cta->toDraft()
        ->withAttributes(SectionAttributes::fromArray([
            'href' => '/strategy',
            'label' => 'Plan the repair',
        ]))
        ->withContent('Bring one broken workflow. Leave with the fix mapped.'),
);

echo $patch->toUnifiedDiff();

$updated = $document->applyPatch($patch);
echo $updated->toMarkdown();
```

## Primitive Contracts

Use primitive definitions when CMS jobs or LLM agents need guardrails.

```php
use Nocturnal\MarkdownSections\Primitive\PrimitiveDefinition;
use Nocturnal\MarkdownSections\Primitive\PrimitiveRegistry;

$registry = (new PrimitiveRegistry())->register(
    PrimitiveDefinition::type('cta')
        ->requireAttributes('href', 'label')
        ->optionalAttributes('variant')
        ->rejectUnknownAttributes()
        ->requireContent(),
);

$result = $registry->validate($document);

if (! $result->isValid()) {
    foreach ($result->issues() as $issue) {
        echo $issue->message . PHP_EOL;
    }
}
```

For agents, export a small edit manifest instead of the whole document:

```php
$manifest = $document->toEditManifest($registry);
```

Each section entry includes type, attributes, content, source span, checksum, validation state, and validation issues.

## Insert And Append

```php
use Nocturnal\MarkdownSections\Value\SectionDraft;
use Nocturnal\MarkdownSections\Value\SectionAttributes;

$faq = new SectionDraft(
    'faq',
    SectionAttributes::fromArray(['title' => 'How does review work?']),
    "The patch is previewed before it is applied.",
);

$document = $document->withInsertedSectionAfter($cta, $faq);
$document = $document->withAppendedSection($faq);
```

These methods return new `MarkdownDocument` instances. The original document is not mutated.

## Syntax

- Opening fence: `:::type` or `:::type{key="value" key2=value}`
- Closing fence: a line whose trimmed content is exactly `:::`
- Type names may contain letters, numbers, `_`, and `-`
- Attribute values may be quoted or unquoted
- Quoted values support escaped quotes and backslashes
- Duplicate attributes are allowed; the last value wins

Malformed sections are ignored by default and exposed through diagnostics:

```php
$diagnostics = MarkdownSections::parse($markdown)->diagnostics();
```

## CommonMark Rendering

Rendering is intentionally separate from editing. Register `MarkdownSectionsExtension` only when you need HTML output.

```php
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Nocturnal\MarkdownSections\CommonMark\MarkdownSectionsExtension;
use Nocturnal\MarkdownSections\Rendering\GenericSectionRenderer;
use Nocturnal\MarkdownSections\Rendering\SectionRendererRegistry;

$registry = (new SectionRendererRegistry())->withFallback(new GenericSectionRenderer());

$environment = new Environment([]);
$environment->addExtension(new CommonMarkCoreExtension());
$environment->addExtension(new MarkdownSectionsExtension($registry));

$html = (new MarkdownConverter($environment))->convert($markdown);
```

Unknown section types use a safe escaped raw-source fallback unless you register a different fallback renderer.

## Limits

Defaults match the original Nocturnal parser:

- 1 MB maximum document size
- 100 sections per document
- 100 KB maximum content per section

## More

- Exhaustive component usage: [`EXAMPLE/README.md`](EXAMPLE/README.md)
- Source preservation contract: [`docs/source-preservation-contract.md`](docs/source-preservation-contract.md)
- LLM editing workflow: [`docs/llm-editing-workflow.md`](docs/llm-editing-workflow.md)
- Primitive recipes: [`docs/primitive-recipes.md`](docs/primitive-recipes.md)

## Nocturnal Migration Notes

The first release is package-first. It does not modify the Nocturnal site.

To migrate later, replace the internal section parser with `MarkdownSections::parse()` and adapt existing section renderers to `SectionRendererInterface`. Existing `:::type{attrs}` content remains compatible.
