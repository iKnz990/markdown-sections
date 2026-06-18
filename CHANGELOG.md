# Changelog

All notable changes to `nocturnal/markdown-sections` will be documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Source-preserving immutable document API for custom fenced Markdown sections.
- Checksummed `DocumentPatch` previews for update, removal, insertion, and append workflows.
- Primitive definitions, registries, validation issues, and edit manifests for LLM/CMS-safe section editing.
- League CommonMark 2.x extension for rendering section container nodes.
- Renderer registry with custom, generic, and safe raw-source fallback renderers.
- Diagnostics for malformed attributes and unclosed sections.
- Examples covering parsing, manipulation, diagnostics, limits, rendering, primitive validation, and patch previews.
