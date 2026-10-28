# Changelog

All notable changes to `nocturnal/markdown-sections` will be documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- Process malformed or oversized section regions once instead of repeatedly scanning nested openers and allocating duplicate diagnostics.
- Keep rendered section boundaries consistent when list or blockquote containers end a fenced code block.
- Reject draft code fences whose apparent closing delimiter is indented as literal content.

## [0.1.0] - 2026-10-28

### Fixed

- Preserve a valid section boundary when inserting after a final section without a trailing newline.
- Reject stale insertion anchors, invalid patch spans, and draft values that escape their section boundaries.
- Ignore fenced and indented code examples when discovering editable sections; preserve fenced code inside sections.
- Keep flat-section validation and rendering consistent; reject nested draft fences and report nested source fences.
- Retain configured parser limits when applying edits.
- Correct public repository links and clarify validation, HTML rendering, and file-write responsibilities.
- Accept PHP's empty attribute-map JSON representation in the published edit-manifest schema.
- Require CommonMark 2.10.2 or later in the 2.x series, excluding older releases with published security advisories.
- Exclude local dependencies, lockfiles, caches, and generated state from Composer archives while retaining source QA files.

### Added

- Source-preserving immutable document API for custom fenced Markdown sections.
- Checksummed `DocumentPatch` previews for update, removal, insertion, and append workflows.
- Primitive definitions, registries, validation issues, and edit manifests for LLM/CMS-safe section editing.
- League CommonMark 2.x extension for rendering section container nodes.
- Renderer registry with custom, generic, and safe raw-source fallback renderers.
- Diagnostics for malformed attributes and unclosed sections.
- Examples covering parsing, manipulation, diagnostics, limits, rendering, primitive validation, and patch previews.
- Nocturnal repair-infrastructure README, SVG diagrams, comparison docs, and edit manifest schema.
- PHP syntax checks, archive inspection, fresh-consumer smoke checks, third-party notices, and CI coverage for PHP 8.2–8.5 plus lowest supported dependencies.
