# Security Policy

## Supported Versions

Security fixes are provided for the latest stable release.

## Reporting A Vulnerability

Please do not report security issues in public GitHub issues.

Report vulnerabilities privately through GitHub Security Advisories when the repository is published. Include:

- affected version or commit
- minimal reproduction
- expected and actual behavior
- impact assessment
- any suggested fix

## Scope

Relevant security issues include parser denial-of-service behavior, unsafe HTML rendering defaults, source rewrite corruption that can cause content injection, and dependency vulnerabilities.

This package escapes unknown section raw source by default. Custom renderers are application code and should escape untrusted values before emitting HTML.
