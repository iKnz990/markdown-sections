# Contributing

Thanks for improving `nocturnal/markdown-sections`.

## Local Setup

```bash
git clone https://github.com/iKnz990/markdown-sections.git
cd markdown-sections
composer install
composer qa
```

`composer qa` runs Composer validation, PHP syntax checks, PHPUnit, PHPStan, example smoke tests, and the 420-line file check. Run `composer check-syntax` separately when investigating syntax errors, and run `composer audit` before preparing a release.

## Development Rules

- Keep files at or below 420 lines.
- Preserve source formatting outside edited section spans.
- Treat patch previews and checksum rejection as part of the public safety contract.
- Keep primitive validation focused on section-level contracts, not full Markdown schema validation.
- Prefer immutable value objects and focused services.
- Keep CommonMark integration separate from the source-preserving document API.
- Add or update tests for parser behavior, document rewriting, and rendering behavior.
- Keep validation and source-identity checks distinct: primitive rules run only when called, and checksums do not provide authorization or filesystem locking.
- Document any change to rendering defaults or untrusted-input handling.

## Pull Requests

Before opening a pull request:

1. Run `composer qa`.
2. Add tests for behavior changes.
3. Update `README.md`, `examples/README.md`, or `CHANGELOG.md` when public behavior changes.
4. Keep the PR focused on one behavior or maintenance task.

Report security concerns through [the private reporting process](SECURITY.md), rather than attaching a sensitive reproduction to an issue or pull request.

## Commit Messages

Use clear imperative messages:

```text
Add source span diagnostics
Fix escaped attribute parsing
Document generic fallback rendering
```
