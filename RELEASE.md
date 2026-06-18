# Release Process

Use this checklist for tagged releases.

## Before Tagging

1. Confirm `README.md`, `EXAMPLE/README.md`, and `CHANGELOG.md` describe the release.
2. Run:

```bash
composer install
composer qa
```

3. Confirm the working tree contains no generated artifacts:

```bash
git status --short
```

`vendor/`, `composer.lock`, `.phpunit.cache/`, coverage output, and IDE files should not be committed.

## Tagging

```bash
git tag v0.1.0
git push origin main --tags
```

## Packagist

After the GitHub repository is published:

1. Submit the repository to Packagist.
2. Enable GitHub service hook or Packagist auto-update.
3. Confirm the package installs with:

```bash
composer require nocturnal/markdown-sections
```

## Versioning

- Patch: bug fixes and docs-only improvements.
- Minor: backward-compatible API additions.
- Major: breaking public API, syntax, rendering, or serialization changes.
