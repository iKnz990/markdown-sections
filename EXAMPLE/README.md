# EXAMPLE

This folder contains runnable examples for every public component in `nocturnal/markdown-sections`.

Run from the package root after `composer install`:

```bash
php EXAMPLE/01-parse-and-inspect.php
php EXAMPLE/02-manipulate-document.php
php EXAMPLE/03-diagnostics-and-limits.php
php EXAMPLE/04-commonmark-rendering.php
php EXAMPLE/05-end-to-end-workflow.php
```

## Component Map

| Component | Demonstrated in | Purpose |
| --- | --- | --- |
| `MarkdownSections::parse()` | `01`, `02`, `03`, `05` | Main consumer entry point for source-preserving parsing. |
| `MarkdownDocument::sections()` | `01`, `02`, `05` | Read parsed sections in source order. |
| `MarkdownDocument::findByType()` | `01`, `05` | Retrieve only sections matching a type. |
| `MarkdownDocument::diagnostics()` | `03` | Read non-fatal malformed section and attribute issues. |
| `MarkdownDocument::withUpdatedSection()` | `02`, `05` | Return a new document with one section replaced. |
| `MarkdownDocument::withoutSection()` | `02` | Return a new document with one section removed. |
| `MarkdownDocument::toMarkdown()` | `02`, `05` | Serialize the source-preserved document. |
| `Section` | `01`, `02`, `05` | Read type, content, raw block, attributes, and source span. |
| `Section::getAttribute()` | `01`, `02`, `05` | Read one attribute with an optional default. |
| `Section::hasAttribute()` | `01` | Check attribute presence. |
| `Section::toDraft()` | `02`, `05` | Start an immutable edit from an existing section. |
| `SectionDraft` | `02`, `05` | Build replacement section Markdown. |
| `SectionAttributes` | `02`, `04`, `05` | Construct and serialize section attribute sets. |
| `SourceSpan` | `01` | Inspect line and byte offsets for editor integrations. |
| `Diagnostic` | `03` | Read parser issue code, message, and line. |
| `ParserLimits` | `03` | Override document, section count, and per-section size limits. |
| `MarkdownSectionsExtension` | `04` | Register section parsing with League CommonMark. |
| `SectionRendererRegistry` | `04` | Register renderers by section type and configure fallback rendering. |
| `SectionRendererInterface` | `04` | Implement custom HTML renderers. |
| `SectionRenderContext` | `04` | Receive type, attributes, rendered child HTML, source lines, and raw source. |
| `GenericSectionRenderer` | `04` | Render unknown/custom sections as semantic generic HTML. |
| `RawSourceSectionRenderer` | `04` | Escape and preserve unknown section source safely. |

## Syntax Coverage

The examples cover:

- simple sections: `:::callout`
- attributes: `key=value` and `key="quoted value"`
- escaped quoted attributes: `title="Use \"quotes\""`
- duplicate attributes where the last value wins
- kebab-case section types like `product-card`
- empty content
- nested Markdown inside sections
- safe handling of unclosed sections
- custom renderers and fallback renderers
- immutable updates that keep the original document unchanged

## Design Notes

The document API is intentionally source-preserving. It does not reformat Markdown outside the section being edited. That makes it suitable for editor tooling, CMS transforms, and content migration scripts where preserving author formatting matters.

The CommonMark extension is the rendering path. It turns section blocks into CommonMark container nodes and lets the child Markdown render normally before passing the HTML to your renderer.
