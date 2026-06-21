# Namespace Visibility Research

This directory is a research wiki for a possible PHP feature:

```php
namespace Acme\Billing\Internal;

private(namespace) class ExactNamespaceOnly {}
protected(namespace) class NamespaceAndDescendants {}
```

and a possible future extension:

```php
namespace Acme\Billing\Infrastructure\Persistence;

protected(namespace: \Acme\Billing)
class BillingTreeOnly {}
```

The documents describe a working hypothesis, not an accepted PHP
specification. No RFC has been published from this repository, and no PHP Wiki
page has been modified.

## Repository Snapshot

Access date: 2026-06-21.

This repository is `php-src`, not a separate research repository:

- Git top-level: `/Users/omelchenko/Developer/c/php-src`
- Remote: `git@github.com:php/php-src.git`
- Branch: `packages`
- Commit: `0fff3ccce2f5f9e0695502a509fa8e8edf8f77d4`
- Describe: `security-audit-2024-10061-g0fff3ccce2f`
- Latest commit inspected: merge from `PHP-8.5`, message
  `Fix GH-22158: JIT observer dispatch through wrong run_time_cache slot`
- PHP version header: `PHP_VERSION 8.6.0-dev`, `PHP_VERSION_ID 80600`

The first iteration is documentation only. It does not modify Zend Engine C
code, generated parser files, stubs, or tests.

## Local Build Environment

Observed tools:

| Tool | Status |
| --- | --- |
| C compiler | Available: Apple clang 21.0.0 |
| Bison | Available: GNU Bison 2.3 |
| re2c | Not available in the current shell |
| Autoconf | Available: GNU Autoconf 2.73 |
| Make | Available: GNU Make 3.81 |
| pkg-config | Available: 2.5.1 |
| `configure` | Not generated in this worktree |
| `Makefile` | Not generated in this worktree |
| `sapi/cli/php` | Not built in this worktree |

The current Bison is older than the version referenced by recent php-src
release process material, and `re2c` is missing. Because `configure`,
`Makefile`, and a CLI binary are absent, PHPT tests were not runnable in this
iteration.

A Docker-based development environment is now available under
[`docker/dev`](../../docker/dev/README.md). The image was built successfully on
2026-06-21 and smoke-tested with:

- GNU Bison 3.8.2;
- re2c 3.0;
- GNU Autoconf 2.71;
- mounted checkout at `/workspaces/php-src`.

## Working Tree State

Before creating this wiki, the worktree had no reported modified, staged, or
untracked files. This iteration intentionally adds only files under
`docs/wiki/`.

## Navigation and Status

| Area | File | Status |
| --- | --- | --- |
| Context | [problem.md](00-context/problem.md) | Research baseline |
| Context | [goals-and-non-goals.md](00-context/goals-and-non-goals.md) | Research baseline |
| Context | [terminology.md](00-context/terminology.md) | Research baseline |
| Context | [working-model.md](00-context/working-model.md) | Working hypothesis |
| Prior art | [php-rfcs.md](01-prior-art/php-rfcs.md) | Source survey |
| Prior art | [other-languages.md](01-prior-art/other-languages.md) | Source survey |
| Prior art | [comparison-matrix.md](01-prior-art/comparison-matrix.md) | Comparative summary |
| Semantics | [syntax-options.md](02-semantics/syntax-options.md) | Proposed prototype choice |
| Semantics | [visibility-rules.md](02-semantics/visibility-rules.md) | Formal draft rules |
| Semantics | [access-matrix.md](02-semantics/access-matrix.md) | Coverage matrix |
| Semantics | [inheritance-and-public-api.md](02-semantics/inheritance-and-public-api.md) | Open design area |
| Semantics | [dynamic-runtime-behavior.md](02-semantics/dynamic-runtime-behavior.md) | Proposed runtime model |
| Semantics | [reflection-autoload-and-aliases.md](02-semantics/reflection-autoload-and-aliases.md) | Proposed with open issues |
| Semantics | [compatibility.md](02-semantics/compatibility.md) | Risk analysis |
| Semantics | [open-questions.md](02-semantics/open-questions.md) | Active questions |
| Semantics | [decisions.md](02-semantics/decisions.md) | Decision log |
| Implementation | [php-src-map.md](03-implementation/php-src-map.md) | Source map |
| Implementation | [parser-and-compiler.md](03-implementation/parser-and-compiler.md) | Implementation notes |
| Implementation | [runtime-enforcement.md](03-implementation/runtime-enforcement.md) | Implementation notes |
| Implementation | [opcache-jit-and-preload.md](03-implementation/opcache-jit-and-preload.md) | Implementation notes |
| Implementation | [reflection-and-tooling.md](03-implementation/reflection-and-tooling.md) | Implementation notes |
| Implementation | [performance.md](03-implementation/performance.md) | Not measured |
| Implementation | [prototype-plan.md](03-implementation/prototype-plan.md) | Phased plan |
| Tests | [test-matrix.md](04-tests/test-matrix.md) | Planned coverage |
| Tests | [phpt-plan.md](04-tests/phpt-plan.md) | Planned tests |
| RFC | [draft.md](05-rfc/draft.md) | English draft skeleton |
| RFC | [alternatives.md](05-rfc/alternatives.md) | Alternatives |
| RFC | [risks-and-backward-compatibility.md](05-rfc/risks-and-backward-compatibility.md) | Risk notes |
| Progress | [roadmap.md](06-progress/roadmap.md) | Roadmap |
| Progress | [findings.md](06-progress/findings.md) | Key findings |
| Progress | [changelog.md](06-progress/changelog.md) | Wiki changelog |

## Source Register

All source claims should either point to an official or primary source, or be
marked as a hypothesis. The main sources used in this iteration are:

- PHP RFC index: <https://wiki.php.net/rfc>
- PHP RFC, Namespace-Scoped Visibility for Methods and Properties:
  <https://wiki.php.net/rfc/namespace_visibility>
- PHP RFC, Namespace Visibility for Class, Interface and Trait:
  <https://wiki.php.net/rfc/namespace-visibility>
- PHP RFC, Attributes v2: <https://wiki.php.net/rfc/attributes_v2>
- PHP RFC, Friends: <https://wiki.php.net/rfc/friends>
- PHP RFC, Class Friendship: <https://wiki.php.net/rfc/friend-classes>
- PHP RFC HOWTO: <https://wiki.php.net/rfc/howto>
- PHP Feature Proposals policy:
  <https://github.com/php/policies/blob/main/feature-proposals.rst>
- php-src PR #20421:
  <https://github.com/php/php-src/pull/20421>
- D language specification:
  <https://dlang.org/spec/attribute.html>
- Rust Reference:
  <https://doc.rust-lang.org/reference/visibility-and-privacy.html>
- Scala 2.13 Language Specification:
  <https://www.scala-lang.org/files/archive/spec/2.13/>
- Java Language Specification, Java SE 24:
  <https://docs.oracle.com/javase/specs/jls/se24/html/>
- C# language reference:
  <https://learn.microsoft.com/en-us/dotnet/csharp/language-reference/>
- Kotlin visibility modifiers:
  <https://kotlinlang.org/docs/visibility-modifiers.html>
- Swift Programming Language, Access Control:
  <https://docs.swift.org/swift-book/documentation/the-swift-programming-language/accesscontrol/>
- Go language specification:
  <https://go.dev/ref/spec>

## Tests Run

No PHPT, build, debug, ZTS, OPcache, or JIT tests were run. The repository is
not configured, the CLI binary is absent, `re2c` is missing, and the available
Bison is old for parser work.

Suggested commands once dependencies and a build are available:

```sh
./buildconf
./configure --enable-debug --enable-zts --enable-opcache
make -j$(sysctl -n hw.ncpu)
TEST_PHP_ARGS="-q" make test TESTS="Zend/tests/access_modifiers"
```

Using the Docker environment:

```sh
PHP_SRC_DEV_UID=$(id -u) PHP_SRC_DEV_GID=$(id -g) \
  docker compose -f docker/dev/compose.yml run --rm php-src-dev
```
