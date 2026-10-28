# Security Policy

## Supported Versions

Security fixes target the latest `0.1.x` release. Reports should identify the affected version or commit; older versions may require an upgrade.

## Reporting A Vulnerability

Please do not report security issues in public GitHub issues.

Use [GitHub private vulnerability reporting](https://github.com/iKnz990/markdown-sections/security/advisories/new). If that option is unavailable, open an issue asking the maintainer for a private reporting channel without including vulnerability details or a reproduction. Include in the private report:

- affected version or commit
- minimal reproduction
- expected and actual behavior
- impact assessment
- any suggested fix

## Scope

Relevant security issues include parser denial-of-service behavior, unsafe HTML rendering defaults, source rewrite corruption that can cause content injection, and dependency vulnerabilities.

The default unknown-section renderer escapes raw section source. Generic and custom renderers can receive child HTML from CommonMark; this does not sanitize the complete document. For untrusted Markdown, configure CommonMark's `html_input` to `escape` or `strip`, disable `allow_unsafe_links`, and set appropriate resource limits. Follow [CommonMark's security guidance](https://commonmark.thephpleague.com/2.x/security/). Custom renderers must validate URL schemes and escape values for their output context.

Primitive registries are opt-in structural validators. Registering rules or applying a patch does not enforce validation automatically. Inspect parser diagnostics and validate the proposed result explicitly before accepting it; use `reportUnknown: true` if unregistered section types must be rejected.

Checksum gates and patch previews protect source identity within the supplied in-memory snapshot. They do not authenticate callers, authorize proposals, or protect a filesystem read/check/write race. Applications own access control, approval policy, locking or compare-and-swap, and persistence. Report any case where a patch applies to a different in-memory source state without rejection.
