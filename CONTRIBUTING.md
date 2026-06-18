# Contributing

Thanks for improving `nocturnal/markdown-sections`.

## Local Setup

```bash
composer install
composer qa
```

`composer qa` runs Composer validation, PHPUnit, PHPStan, example smoke tests, and the 420-line file check.

## Development Rules

- Keep files at or below 420 lines.
- Preserve source formatting outside edited section spans.
- Treat patch previews and checksum rejection as part of the public safety contract.
- Keep primitive validation focused on section-level contracts, not full Markdown schema validation.
- Prefer immutable value objects and focused services.
- Keep CommonMark integration separate from the source-preserving document API.
- Add or update tests for parser behavior, document rewriting, and rendering behavior.

## Pull Requests

Before opening a pull request:

1. Run `composer qa`.
2. Add tests for behavior changes.
3. Update `README.md`, `EXAMPLE/README.md`, or `CHANGELOG.md` when public behavior changes.
4. Keep the PR focused on one behavior or maintenance task.

## Commit Messages

Use clear imperative messages:

```text
Add source span diagnostics
Fix escaped attribute parsing
Document generic fallback rendering
```
