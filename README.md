# nocturnal/markdown-sections

`nocturnal/markdown-sections` edits custom fenced Markdown sections without reformatting the rest of the document:

```markdown
:::callout{variant="info" title="Note"}
Markdown content goes here.
:::
```

It is built for content systems, CMS tools, migrations, and AI-assisted editing flows where user-authored Markdown should stay intact.

It provides two layers:

- a source-preserving immutable document API for section-level edits
- a League CommonMark 2.x extension for rendering sections as container nodes

The package is based on the section syntax originally used by Nocturnal Marketing, but it is standalone and MIT licensed.

## Install

```bash
composer require nocturnal/markdown-sections
```

For local development:

```bash
composer install
composer qa
```

## Why This Package

Most Markdown tooling focuses on rendering Markdown to HTML. That is useful, but it is not enough when you need to safely edit a structured block inside an existing Markdown document.

This package treats source-preserving section edits as the primary API:

- update one `:::cta` without changing surrounding prose
- migrate attributes across many documents
- remove deprecated sections while preserving author formatting
- inspect source line and byte offsets for editor integrations
- render sections through CommonMark only when HTML output is needed

## Parse And Manipulate

```php
use Nocturnal\MarkdownSections\MarkdownSections;
use Nocturnal\MarkdownSections\Value\SectionAttributes;

$document = MarkdownSections::parse($markdown);
$callout = $document->findByType('callout')[0];

$updated = $document->withUpdatedSection(
    $callout,
    $callout->toDraft()
        ->withAttributes(SectionAttributes::fromArray(['variant' => 'warning']))
        ->withContent('Updated **Markdown** content.'),
);

echo $updated->toMarkdown();
```

The document API rewrites only known section source spans. Markdown outside edited sections is preserved exactly.

For exhaustive component usage, see [`EXAMPLE/README.md`](EXAMPLE/README.md).

## CommonMark Rendering

```php
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Nocturnal\MarkdownSections\CommonMark\MarkdownSectionsExtension;
use Nocturnal\MarkdownSections\Rendering\SectionRenderContext;
use Nocturnal\MarkdownSections\Rendering\SectionRendererInterface;
use Nocturnal\MarkdownSections\Rendering\SectionRendererRegistry;

$renderer = new class implements SectionRendererInterface {
    public function render(SectionRenderContext $context): string
    {
        return '<aside>' . $context->innerHtml . '</aside>';
    }
};

$registry = (new SectionRendererRegistry())->register('callout', $renderer);

$environment = new Environment([]);
$environment->addExtension(new CommonMarkCoreExtension());
$environment->addExtension(new MarkdownSectionsExtension($registry));

$html = (new MarkdownConverter($environment))->convert($markdown);
```

Unknown section types use a safe escaped raw-source fallback. To render unknown sections generically:

```php
use Nocturnal\MarkdownSections\Rendering\GenericSectionRenderer;
use Nocturnal\MarkdownSections\Rendering\SectionRendererRegistry;

$registry = (new SectionRendererRegistry())->withFallback(new GenericSectionRenderer());
```

## Syntax

- Opening fence: `:::type` or `:::type{key="value" key2=value}`
- Closing fence: a line whose trimmed content is exactly `:::`
- Type names may contain letters, numbers, `_`, and `-`
- Attribute values may be quoted or unquoted
- Quoted values support escaped quotes and backslashes
- Duplicate attributes are allowed; the last value wins

Malformed sections are ignored by the document parser and exposed through diagnostics:

```php
$diagnostics = MarkdownSections::parse($markdown)->diagnostics();
```

## Limits

Defaults match the original Nocturnal parser:

- 1 MB maximum document size
- 100 sections per document
- 100 KB maximum content per section

## Nocturnal Migration Notes

The first release is package-first. It does not modify the Nocturnal site.

To migrate later, replace the internal section parser with `MarkdownSections::parse()` and adapt existing section renderers to `SectionRendererInterface`. Existing `:::type{attrs}` content remains compatible.
