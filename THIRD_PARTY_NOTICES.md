# Third-Party Notices

The library source is licensed under [MIT](LICENSE). Third-party dependencies and adapted material retain their own licenses; this file does not relicense them.

## Composer Dependencies

Dependencies are installed separately by Composer. The source distribution does not bundle `vendor/`. Retain each dependency's copyright notices, license text, and other required notices if you redistribute an installation or bundled application.

The following runtime dependency snapshot was checked on October 28, 2026. Versions vary with the consuming application's dependency resolution; inspect its installed packages with `composer licenses` and preserve their included license files.

The package requires CommonMark `^2.10.2`. [CommonMark 2.10.2](https://github.com/thephpleague/commonmark/releases/tag/2.10.2) includes fixes for published table-parser denial-of-service and raw-HTML-filtering advisories; older 2.x releases are excluded by this package's dependency constraint.

| Package | Inspected version | Upstream license |
| --- | --- | --- |
| `league/commonmark` | `2.10.4` | [BSD-3-Clause](https://github.com/thephpleague/commonmark/blob/2.10.4/LICENSE) |
| `league/config` | `1.2.0` | [BSD-3-Clause](https://github.com/thephpleague/config/blob/v1.2.0/LICENSE.md) |
| `dflydev/dot-access-data` | `3.0.3` | [MIT](https://github.com/dflydev/dflydev-dot-access-data/blob/v3.0.3/LICENSE) |
| `nette/schema` | `1.4.0` | [BSD-3-Clause, alternatively GPL-2.0 or GPL-3.0](https://github.com/nette/schema/blob/v1.4.0/license.md) |
| `nette/utils` | `4.1.6` | [BSD-3-Clause, alternatively GPL-2.0 or GPL-3.0](https://github.com/nette/utils/blob/v4.1.6/license.md) |
| `psr/event-dispatcher` | `1.0.0` | [MIT](https://github.com/php-fig/event-dispatcher/blob/1.0.0/LICENSE) |
| `symfony/deprecation-contracts` | `3.7.1` | [MIT](https://github.com/symfony/deprecation-contracts/blob/v3.7.1/LICENSE) |
| `symfony/polyfill-php80` | `1.43.0` | [MIT](https://github.com/symfony/polyfill-php80/blob/v1.43.0/LICENSE) |

Nette's BSD and GPL terms are alternatives; its upstream license explicitly permits choosing the BSD terms. A list of alternatives is not a requirement to combine all three licenses. Development tools, including PHPUnit, PHPStan, and their dependencies, also retain the notices supplied with those packages.

## Code of Conduct

[CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) is a shortened adaptation of the [Contributor Covenant, version 2.1](https://www.contributor-covenant.org/version/2/1/code_of_conduct/), by Coraline Ada Ehmke and contributors. The adapted document is licensed under [CC BY 4.0](https://creativecommons.org/licenses/by/4.0/), consistent with the [upstream version 2.1 license](https://github.com/EthicalSource/contributor_covenant/blob/2.1/LICENSE.md). Its attribution and change notice must be retained.

## Documentation Assets

The SVG diagrams in `docs/assets/` are repository source assets covered by the repository's MIT license. They use local shapes and text rather than bundled third-party images. Font-family names in the SVGs request fonts available on the viewer's system; no font files are distributed. Any separately obtained font remains subject to its own license.
