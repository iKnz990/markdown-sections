# Comparison

`nocturnal/markdown-sections` is not competing to be the best Markdown renderer.

Its lane is narrower: safely patch named source spans in Markdown documents.

## The Short Version

| Tool category | What it is good at | Where this package differs |
| --- | --- | --- |
| League CommonMark | Standards-oriented Markdown parsing and HTML rendering. | This package uses League CommonMark for rendering, but its main API is source-preserving section edits. |
| CommonMark container extensions | Custom block syntax rendered to HTML. | Rendering is optional here. Patch previews, checksums, and primitive validation are the core workflow. |
| remark / mdast directives | JavaScript Markdown AST and directive tooling. | This package is PHP-first and does not ask consumers to serialize the whole Markdown AST back to source. |
| MDX | Rich component authoring inside Markdown-like files. | This package does not replace MDX. It gives existing Markdown files bounded editable primitives. |
| WordPress shortcodes | CMS-native embedded content blocks. | This package is framework-agnostic and exposes source spans, checksums, and immutable patches. |
| Generic Markdown parsers | Tokenizing, AST building, or HTML conversion. | This package treats unbounded rewrites as the problem and limits mutation to known section spans. |

## Why The Distinction Matters

Agents and migration scripts can read Markdown easily. That is useful until they rewrite parts of the file they were never asked to touch.

This package narrows the edit surface:

1. Parse named sections.
2. Validate primitive contracts.
3. Export a bounded manifest.
4. Preview a diff.
5. Apply only if the checksum still matches.

## What This Is

- bounded section editor
- patch previewer
- primitive validator
- agent manifest generator
- optional League CommonMark renderer

## What This Is Not

- full Markdown AST round-tripper
- magic AI editor
- MDX replacement
- generic CMS
- whole-file serializer

## Decision Rule

Use `nocturnal/markdown-sections` when the question is:

> Can this application safely change one named Markdown primitive without touching the rest of the file?

Use a Markdown renderer or AST toolkit when the question is:

> Can this application parse or render the whole Markdown document?
