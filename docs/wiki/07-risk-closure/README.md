# Risk Closure Phase

This directory closes the known gaps from the first research pass for PHP
class-level namespace visibility. It intentionally narrows the first RFC to
exact namespace class-like visibility:

```php
namespace Acme\Billing;

private(namespace) class InternalService {}
```

The proposal in this phase does not include namespace descendants, explicit
roots, `protected(namespace)`, `private class`, `internal`, modules, friend
namespaces, or native consistent accessibility.

## Repository Snapshot

| Item | Value |
| --- | --- |
| Branch | `packages` |
| Commit | `1c1d3a699c624030ca0582daedfebcba723c8ddc` |
| Working tree at start | Clean |
| Access date for external sources | 2026-06-21 |

## Source Register

These sources are primary inputs, not binding specifications for this draft.
Draft RFCs and mailing-list posts are treated as prior art only.

| Source | Status | Last edit or update | Current decisions | Stale decisions | Conflict with this model |
| --- | --- | --- | --- | --- | --- |
| <https://wiki.php.net/rfc/namespace_visibility> | Under Discussion, v1.2 | RFC date 2025-11-10; last modified 2025-11-10 18:48 by withinboredom | Member/property `private(namespace)` is exact lexical namespace; class-level visibility is Future Scope; `protected(namespace)` is Future Scope for namespace plus inheritance semantics | Reflection/member bypass details do not decide class-name visibility | Reusing `protected(namespace)` for descendants would conflict with its Future Scope meaning |
| <https://github.com/php/php-src/pull/20421> | Open PR, not merged | Created 2025-11-07; updated 2026-03-14; head `1618f8b0fa0a774627aa8bd1d76749cb1142000c` | Adds member-level namespace visibility, `op_array->namespace_name`, eval/closure/opcache fixes, callable checks | It implements methods/properties, not class-like names | Trait handling uses receiver/using class namespace; class-level trait-body semantics need a separate decision |
| <https://wiki.php.net/rfc/namespace-visibility> | Draft | RFC date 2018-07-18; last modified 2025-04-03 13:08 by 127.0.0.1 | Class/interface/trait top-level visibility; already obtained objects remain usable; Reflection can inspect | Uses plain `private`/`protected`; implementation section is unspecified | `protected` means shared higher-level namespace, not this RFC's exact-only v1 |
| <https://wiki.php.net/rfc/private-classes-and-functions> | Draft | RFC date 2024-12-11; last modified 2025-04-03 13:08 by 127.0.0.1 | Plain `private class` / `private function` are file or namespace-block private via name mangling; namespace runtime visibility is Future Scope | Compile-time mangling deliberately permits alias escape | Plain `private class` is occupied by a different model |
| <https://wiki.php.net/rfc/encapsulation> | Draft | RFC date 2015-02-19; last modified 2025-04-03 13:08 by 127.0.0.1 | Private class/interface/trait exact namespace access; no object membrane; types remained usable outside | Plain `private`; PHP 7.0 target; old Reflection API shape | Broader type allowance is weaker than symbol-visibility v1 |
| <https://externals.io/message/127466> | Mailing-list discussion | Externals archive has no edit timestamp; thread visible as 2025 discussion; accessed 2026-06-21 | Namespace-as-module and module/package boundary objections; explicit prefix roots discussed | Not an RFC | Reinforces that namespace is not ownership or security |
| <https://externals.io/message/51562> | Mailing-list discussion | Externals archive has no edit timestamp; visible as 2011-era discussion; accessed 2026-06-21 | Early class access modifier debate; returned objects remain usable | Plain `public/protected/private class` vocabulary | Confirms long-standing `protected` ambiguity |
| <https://externals.io/message/119893> | Mailing-list discussion | Externals archive has no edit timestamp; visible as 3-year-old modules discussion; accessed 2026-06-21 | Modules could provide a true boundary; namespace-only cannot solve package/version isolation | Not a class visibility RFC | Supports deferring `internal` to modules |
| <https://wiki.php.net/rfc/friend-classes> | Declined | RFC date 2017-09-21; last modified 2025-04-03 13:08 by 127.0.0.1 | Friendship is explicit per-class privileged access; final vote 6 yes, 27 no | Friend namespace future ideas were not accepted | Friend namespaces are a different feature |
| <https://wiki.php.net/rfc/voting> | Accepted process RFC | Date 2011-06-05; updated 2019-02-22; last modified 2025-04-03 13:08 by 127.0.0.1 | Language changes require discussion and 2/3 primary vote; independent primary decisions should not be hidden as secondary details | None for this work | Requires splitting descendants/root/modules into separate votes |

## Status Counts

| Status | Count |
| --- | ---: |
| RESOLVED | 10 |
| MITIGATED | 3 |
| DEFERRED | 2 |
| ACCEPTED | 2 |
| BLOCKED | 0 |

## Documents

| Document | Purpose |
| --- | --- |
| [01-risk-register.md](01-risk-register.md) | Closed risk table with final statuses |
| [02-minimal-scope.md](02-minimal-scope.md) | Scope A/B/C comparison and v1 selection |
| [03-syntax-decision.md](03-syntax-decision.md) | Syntax bake-off and selected syntax |
| [04-normative-semantics.md](04-normative-semantics.md) | Draft normative text |
| [05-operation-coverage.md](05-operation-coverage.md) | Operation matrix and single access invariant |
| [06-lexical-scope.md](06-lexical-scope.md) | Lexical namespace analysis |
| [07-runtime-and-caches.md](07-runtime-and-caches.md) | Class-entry path and cache coverage map |
| [08-reflection-autoload-aliases.md](08-reflection-autoload-aliases.md) | Reflection, autoload, alias policy |
| [09-public-api-and-types.md](09-public-api-and-types.md) | Public API exposure policy |
| [10-compatibility-and-security.md](10-compatibility-and-security.md) | BC and security non-goals |
| [11-performance-evidence.md](11-performance-evidence.md) | Performance evidence template and current status |
| [12-adversarial-review.md](12-adversarial-review.md) | Strongest objections and responses |
| [13-rfc-splitting.md](13-rfc-splitting.md) | RFC sequence |
| [14-implementation-gates.md](14-implementation-gates.md) | Required implementation gates |
| [15-accepted-risks.md](15-accepted-risks.md) | Remaining fundamental limits |
| [16-acceptance-checklist.md](16-acceptance-checklist.md) | Phase acceptance checklist |
