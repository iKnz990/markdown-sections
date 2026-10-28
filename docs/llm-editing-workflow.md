# LLM Editing Workflow

The safe path is to give an agent a manifest, not the whole file.

![Agent-safe edit loop](assets/agent-safe-edit-loop.svg)

## Flow

1. Parse the Markdown.
2. Reject or handle parser diagnostics; explicitly validate primitives with a `PrimitiveRegistry`.
3. Export `MarkdownDocument::toEditManifest()`.
4. Let the agent choose one section and return a proposed draft.
5. Build a `DocumentPatch`.
6. Show `DocumentPatch::toUnifiedDiff()` to a human or CI check.
7. Load the current source and apply the reviewed patch only if its document checksum still matches.
8. Inspect diagnostics and explicitly validate the proposed result before saving it.
9. Persist under application-level locking or compare-and-swap to protect the read/check/write operation.

The manifest shape is documented in [`edit-manifest.schema.json`](edit-manifest.schema.json).

When encoding the PHP manifest with `json_encode()`, an empty attribute map is `[]`; a nonempty map is a JSON object. The schema accepts both empty-map representations (`[]` and `{}`), while rejecting nonempty attribute lists. Do not apply `JSON_FORCE_OBJECT` to the whole manifest: `sections` and `issues` must remain arrays.

## Why This Works

Markdown is easy for models to read, but broad document rewrites are risky. A primitive manifest narrows the task to a named block with attributes, content, source span, checksum, and validation issues.

The agent can edit the primitive. The application controls whether the patch applies.

`applyPatch()` does not invoke primitive validation, authenticate an agent, or grant write access. `toEditManifest()` also does not enforce an edit policy. When unknown section types must be rejected, call `validate($document, reportUnknown: true)` explicitly. Inspect diagnostics as well: malformed sections can be omitted from the parsed sections without causing registry validation to fail.

Section content and attributes are still untrusted input. Presence/type contracts do not sanitize HTML or approve URL schemes; configure CommonMark and custom renderers accordingly.

## Recommended Agent Prompt Shape

```text
You may edit only one manifest section.
Keep the same section type unless asked.
Return attributes and content only.
Do not rewrite surrounding Markdown.
If validation issues exist, fix those first.
```

## Failure Modes To Keep

- stale checksum
- missing required attribute
- unknown attribute
- empty required content
- malformed section syntax
- patch rejected before write
