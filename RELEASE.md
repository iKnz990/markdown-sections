# Release Process

Use this checklist for the first public MIT release and later tagged releases. Preparing a candidate, making the GitHub repository public, publishing a GitHub release, and submitting to Packagist are separate steps.

## Before Tagging

1. Review the source, documentation, examples, assets, dependency licenses, and repository history intended for public distribution. Keep the original MIT license and applicable third-party notices.
2. Confirm the canonical repository URL is `https://github.com/iKnz990/markdown-sections` throughout package metadata and documentation.
3. Confirm `README.md`, `examples/README.md`, and `CHANGELOG.md` describe the candidate without claiming that Packagist or a public release already exists.
4. Merge the reviewed change and record its full commit SHA. Verify all required CI jobs for that exact commit, including the supported PHP versions. A local run verifies only its own PHP version.
5. In a clean checkout of that reviewed commit, run:

```bash
composer install
composer qa
composer audit
```

6. Create and inspect a distribution archive:

```bash
composer archive --format=zip --dir=build --file=markdown-sections
composer check-archive -- build/markdown-sections.zip
```

The inspection helper requires PHP's zip extension. The source archive retains tests and tools so a downloaded checkout can run `composer qa`; local dependency installations and generated state are excluded. Install the extracted archive in a fresh consumer application without development dependencies, then run `php tools/check-consumer.php path/to/consumer/vendor/autoload.php` from the source checkout. This exercises parsing, validation, patching, rendering, CRLF preservation, and stale-patch rejection. The CI workflow contains a complete consumer-install example.
7. Confirm the working tree contains no generated artifacts:

```bash
git status --short
```

`vendor/`, `composer.lock`, `.phpunit.cache/`, coverage output, and IDE files should not be committed.

8. Review the prepared candidate before changing repository visibility. GitHub private vulnerability reporting requires a public repository: enable it immediately after the visibility change and verify the reporting link in `SECURITY.md` before publishing or announcing the release. Check that public Issues and the contribution/support instructions work. Until that transition, the private-reporting setup remains pending.

## Tagging

For the first release, use `v0.1.0`. Do not tag an unreviewed working tree or push unrelated tags. Replace the placeholder below with the full reviewed commit SHA, then run from the clean release checkout:

```bash
set -eu
RELEASE_SHA=REPLACE_WITH_FULL_REVIEWED_COMMIT_SHA
git fetch origin main
git switch --detach "$RELEASE_SHA"
test "$(git rev-parse HEAD)" = "$RELEASE_SHA"
git merge-base --is-ancestor "$RELEASE_SHA" origin/main
test -z "$(git status --porcelain)"
git tag -a v0.1.0 "$RELEASE_SHA" -m "Release v0.1.0"
git push origin refs/tags/v0.1.0
```

Create a reviewable GitHub draft from the existing tag:

```bash
gh release create v0.1.0 --verify-tag --draft --title v0.1.0 --generate-notes
```

Review the notes and assets, confirm the tag resolves to the recorded SHA, and publish the GitHub release only when public release is intended. Changing repository visibility alone does not publish a release or register a Composer package.

## Packagist

Before a public Packagist release:

1. Confirm the GitHub repository and the reviewed version tag are publicly accessible.
2. Check availability of the `nocturnal/markdown-sections` name, then submit the canonical repository URL to Packagist under the intended maintainer account.
3. Enable Packagist auto-update and verify that it indexes the intended tag.
4. In a fresh application with no custom VCS repository configuration, confirm the public package installs with:

```bash
composer require nocturnal/markdown-sections:^0.1
```

5. Run the README demo against that installation. Only then change the README to advertise a normal Packagist install as available.

## Versioning

- Patch: bug fixes and docs-only improvements.
- Minor: backward-compatible API additions.
- Major: breaking public API, syntax, rendering, or serialization changes.
