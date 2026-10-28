# Source Preservation Contract

`nocturnal/markdown-sections` is not a general Markdown-to-Markdown serializer.

The package rewrites known section spans and preserves everything else exactly.

![Source preservation contract](assets/source-preservation-contract.svg)

## Guarantees

- Markdown outside an edited section span is copied byte-for-byte.
- A section exposes its source lines, byte offsets, raw source, and checksum.
- Patches are created against a document checksum.
- Applying a patch to a different document state throws an exception.
- Section updates regenerate only the selected section block.
- Removal deletes only the selected section block.
- Insert and append operations add generated section Markdown without reparsing unrelated prose.

## Non-Goals

- It does not normalize headings, lists, code fences, links, or prose.
- It does not promise full CommonMark AST round-tripping.
- It does not try to merge conflicting edits.
- Registering a primitive definition does not automatically validate an edit. Call the registry before and after applying a proposed patch, and inspect parser diagnostics separately.

## Application Responsibilities

The checksum gate compares the patch against the supplied in-memory `MarkdownDocument`. Applying it to an old object cannot detect that another process has changed a stored file. The library does not provide authorization, file locking, atomic writes, signatures, or automatic conflict merging.

Load the current source before applying the reviewed patch. Keep the read/check/write operation inside an application-level lock or use storage compare-and-swap so a concurrent change cannot be overwritten after the checksum check. Rebuild and review a patch when its source has changed.

Primitive contracts check section structure, not arbitrary HTML or the meaning of attribute values. Unregistered types are ignored unless `validate($document, reportUnknown: true)` is used. A successful validation does not override parser diagnostics or authorize persistence.

## Review Rule

Every behavior change that touches rewriting should include a before/after test where surrounding Markdown remains unchanged.
