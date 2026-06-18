# nocturnal/markdown-sections

![Safe Markdown edits for agents](docs/assets/hero-safe-markdown-edits.svg)

**Safe Markdown edits for agents.**

`nocturnal/markdown-sections` gives PHP applications a bounded editing surface inside Markdown. Parse named sections, validate contracts, preview exact diffs, and reject stale edits before writing.

**Untouched Markdown stays byte-for-byte untouched.**

Rendering is included. Source-safe editing is the product.

`PHP 8.2+` · `League CommonMark 2.x` · `source spans` · `SHA-256 checksums` · `patch previews` · `primitive validation`

## Install

This private repository is not on Packagist yet.

For private GitHub use:

```bash
composer config repositories.nocturnal-markdown-sections vcs https://github.com/Nocturnal-Marketing/markdown-sections
composer require nocturnal/markdown-sections:dev-main
```

For local development:

```bash
composer install
composer qa
```

When the package is published publicly, the install path becomes:

```bash
composer require nocturnal/markdown-sections
```

## 60-Second Patch Demo

Markdown is readable. Broad rewrites are dangerous. This package gives agents and CMS jobs one section, one contract, one diff, and one checksum gate.

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

![Patch receipt](docs/assets/patch-receipt.svg)

## Why This Exists

Most Markdown packages optimize for parsing and rendering. That is useful, but it does not solve the dangerous part of agent workflows: unbounded mutation.

This package is a source-preserving patch engine for named Markdown primitives:

- parse sections such as `:::cta`, `:::faq`, `:::steps`, and `:::product-card`
- expose line numbers, byte offsets, raw source, and SHA-256 checksums
- validate project-defined primitive contracts
- preview exact patches before mutation
- reject stale edits when the document checksum changed
- update, remove, insert, or append sections without serializing the whole Markdown AST
- render through League CommonMark only when HTML output is needed

This is not a Markdown serializer. It is a bounded patch surface.

## Patch Workflow

![Agent-safe edit loop](docs/assets/agent-safe-edit-loop.svg)

The safe path is:

1. Parse Markdown.
2. Validate primitives.
3. Export a manifest.
4. Let the agent edit one section.
5. Preview the diff.
6. Check the document checksum.
7. Apply the patch.

## Primitive Contracts

![Section anatomy](docs/assets/section-anatomy.svg)

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

## LLM-Safe Edit Manifest

Agents get a manifest, not the source file.

```php
$manifest = $document->toEditManifest($registry);
```

Each section entry includes type, attributes, content, source span, checksum, validation state, and validation issues.

See [`docs/edit-manifest.schema.json`](docs/edit-manifest.schema.json) for the protocol shape.

## Source Preservation Contract

![Source preservation contract](docs/assets/source-preservation-contract.svg)

The contract is direct:

- Markdown outside the edited section span is copied byte-for-byte.
- Patches are tied to the document checksum that produced them.
- Applying a patch to a different document state throws.
- Section updates regenerate only the selected section block.
- Insert and append operations add generated section Markdown without reparsing unrelated prose.

Preservation contract tested: when a section changes, prefix and suffix remain byte-for-byte identical.

## Failure Modes

| Failure mode | Detected by | Result | Safe to retry |
| --- | --- | --- | --- |
| stale checksum | `DocumentPatch::applyTo()` | patch rejected | yes |
| missing attribute | `PrimitiveRegistry::validate()` | validation issue | yes |
| unknown attribute | `PrimitiveDefinition::rejectUnknownAttributes()` | validation issue | yes |
| empty required content | `PrimitiveDefinition::requireContent()` | validation issue | yes |
| malformed section syntax | parser diagnostics | section ignored or diagnostic emitted | yes |
| oversized document or section | parser limits | exception or diagnostic | after reducing input |

Proof before mutation. Diff first. Checksum first.

## Insert And Append

```php
use Nocturnal\MarkdownSections\Value\SectionAttributes;
use Nocturnal\MarkdownSections\Value\SectionDraft;

$faq = new SectionDraft(
    'faq',
    SectionAttributes::fromArray(['title' => 'How does review work?']),
    'The patch is previewed before it is applied.',
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

## Rendering Via League CommonMark

Rendering is optional. Source safety is the point.

Register `MarkdownSectionsExtension` only when you need HTML output.

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

## Where It Fits

![Integration architecture](docs/assets/integration-architecture.svg)

Use it in CMS tasks, GitHub PR bots, AI content repair, static-site migrations, and content pipelines where "probably fine" is not good enough.

## Limits

Defaults match the original Nocturnal parser:

- 1 MB maximum document size
- 100 sections per document
- 100 KB maximum content per section

## More

- Exhaustive component usage: [`examples/README.md`](examples/README.md)
- Source preservation contract: [`docs/source-preservation-contract.md`](docs/source-preservation-contract.md)
- LLM editing workflow: [`docs/llm-editing-workflow.md`](docs/llm-editing-workflow.md)
- Primitive recipes: [`docs/primitive-recipes.md`](docs/primitive-recipes.md)
- Prior-art comparison: [`docs/comparison.md`](docs/comparison.md)
- Brand system and asset inventory: [`docs/brand-system.md`](docs/brand-system.md)

## Nocturnal Migration Notes

The first release is package-first. It does not modify the Nocturnal site.

To migrate later, replace the internal section parser with `MarkdownSections::parse()` and adapt existing section renderers to `SectionRendererInterface`. Existing `:::type{attrs}` content remains compatible.
