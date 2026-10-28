# Examples

This folder contains runnable examples for every public component in `nocturnal/markdown-sections`.

Run from the package root after `composer install`:

```bash
php examples/01-parse-and-inspect.php
php examples/02-manipulate-document.php
php examples/03-diagnostics-and-limits.php
php examples/04-commonmark-rendering.php
php examples/05-end-to-end-workflow.php
php examples/06-llm-safe-primitives.php
php examples/07-patch-preview-and-insertions.php
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
| `MarkdownDocument::checksum()` | `06` | Read the document checksum used for stale patch rejection. |
| `MarkdownDocument::previewUpdatedSection()` | `06` | Build a patch preview before applying an update. |
| `MarkdownDocument::previewWithoutSection()` | `07` | Build a patch preview before removing a section. |
| `MarkdownDocument::previewInsertSectionAfter()` | `07` | Build an insertion patch at a known source point. |
| `MarkdownDocument::withInsertedSectionBefore()` | `02` | Insert a generated section before another section. |
| `MarkdownDocument::withInsertedSectionAfter()` | `07` | Insert a generated section after another section. |
| `MarkdownDocument::withAppendedSection()` | `07` | Append a generated section to the document. |
| `MarkdownDocument::applyPatch()` | `06`, `07` | Apply a patch only when its source checksums still match. |
| `MarkdownDocument::toEditManifest()` | `06` | Export a bounded manifest for LLM or CMS edit workflows. |
| `MarkdownDocument::toMarkdown()` | `02`, `05` | Serialize the source-preserved document. |
| `Section` | `01`, `02`, `05` | Read type, content, raw block, attributes, and source span. |
| `Section::getAttribute()` | `01`, `02`, `05` | Read one attribute with an optional default. |
| `Section::hasAttribute()` | `01` | Check attribute presence. |
| `Section::checksum()` | `06` | Read the section source checksum. |
| `Section::lineEnding()` | `06`, `07` | Preserve section line endings for generated replacements. |
| `Section::toDraft()` | `02`, `05` | Start an immutable edit from an existing section. |
| `SectionDraft` | `02`, `05` | Build replacement section Markdown. |
| `SectionAttributes` | `02`, `04`, `05` | Construct and serialize section attribute sets. |
| `SourceSpan` | `01` | Inspect line and byte offsets for editor integrations. |
| `SourceSpan::toArray()` | `06` | Export source spans in manifests. |
| `Diagnostic` | `03` | Read parser issue code, message, and line. |
| `ParserLimits` | `03` | Override document, section count, and per-section size limits. |
| `DocumentPatch` | `06`, `07` | Preview and apply checksummed document edits. |
| `PatchOperation` | `06`, `07` | Internal patch operation value exposed through patch previews. |
| `PrimitiveDefinition` | `06` | Declare required primitive attributes and content rules. |
| `PrimitiveRegistry` | `06` | Register primitive definitions and validate documents. |
| `PrimitiveValidationResult` | `06` | Read aggregate primitive validation state. |
| `PrimitiveIssue` | `06` | Read validation issue code, message, type, section index, and line. |
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
- patch previews with stale document checksum rejection
- primitive validation for CMS and LLM edit contracts
- insert-after and append workflows for creating primitives
- bounded edit manifests for agent workflows

## Design Notes

The document API is intentionally source-preserving. It does not reformat Markdown outside the section being edited. That makes it suitable for editor tooling, CMS transforms, and content migration scripts where preserving author formatting matters.

Primitive definitions are optional contracts layered on top of parsed sections. They let applications validate attributes and content before asking an agent, migration, or CMS job to create a patch.

The examples print their results and do not authorize or persist edits. Call `PrimitiveRegistry::validate()` explicitly before and after applying a proposed patch, inspect `diagnostics()`, and reject issues before saving. Use `reportUnknown: true` when unregistered primitive types should be invalid. Applying a patch does not run these checks automatically.

The CommonMark extension is the rendering path. It turns section blocks into CommonMark container nodes and lets the child Markdown render normally before passing the HTML to your renderer.

For untrusted Markdown, configure CommonMark's `html_input`, `allow_unsafe_links`, and nesting limits. Custom renderers must validate and escape their own attribute values. See the [security policy](../SECURITY.md) and [CommonMark security guidance](https://commonmark.thephpleague.com/2.x/security/).
