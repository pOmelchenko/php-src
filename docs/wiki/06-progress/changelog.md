# Changelog

## 2026-06-21

- Created research wiki under `docs/wiki`.
- Recorded php-src repository snapshot and local build environment.
- Added problem statement, goals, terminology, and working model.
- Surveyed PHP RFC prior art and PR #20421.
- Surveyed D, Rust, Scala, Java, C#, Kotlin, Swift, and Go.
- Compared syntax alternatives and selected `private(namespace)` /
  `protected(namespace)` as the first prototype candidate.
- Formalized base visibility rules.
- Added access matrix and PHPT plan.
- Added inheritance/public API analysis.
- Added dynamic runtime behavior notes.
- Added reflection/autoload/aliases analysis.
- Added compatibility and risk notes.
- Added php-src source map with parser/compiler/runtime/reflection/OPcache/JIT
  hotspots.
- Added phased prototype plan.
- Added English RFC draft as an unassigned research draft.
- No C code, generated files, or PHPT tests were added.
- Added Docker development environment under `docker/dev`.
- Built and smoke-tested the Docker image with Bison 3.8.2, re2c 3.0, and
  Autoconf 2.71.
- Started an incomplete experimental Phase B parser/metadata spike.
- Added scanner/parser support for `private(namespace)` and
  `protected(namespace)` on named class, interface, trait, and enum
  declarations.
- Added class-entry metadata, ReflectionClass metadata methods, tokenizer
  token data, and OPcache metadata persistence plumbing for the Phase B spike.
- Added six PHPT tests covering accepted syntax, rejected syntax, Reflection
  metadata, and tokenizer tokens.
- Verified a Docker debug build, targeted PHPT run, and Docker ZTS debug build.
- Phase B did not implement runtime access enforcement.
- Committed the Phase B parser/metadata spike as `af9e9790f52`.
- Started incomplete experimental Phase C runtime enforcement.
- Added a central class namespace visibility check and wired it into `ZEND_NEW`
  and `ZEND_FETCH_CLASS`.
- Added five Phase C PHPT tests for static `new`, dynamic `new $class`, method
  caller namespace, segment-prefix rejection, and cache-order checks.
- Verified Docker debug build, 11/11 targeted PHPT tests, and Docker ZTS debug
  build for the Phase C spike.
- Started Phase 2 risk closure after commit
  `1c1d3a699c624030ca0582daedfebcba723c8ddc` on branch `packages`.
- Created `docs/wiki/07-risk-closure` with source register, risk register,
  minimal scope, syntax decision, normative semantics, operation coverage,
  lexical scope, runtime/cache map, Reflection/autoload/alias policy, public API
  policy, compatibility/security analysis, performance evidence status,
  adversarial review, RFC splitting, implementation gates, accepted risks, and
  acceptance checklist.
- Selected exact-only `private(namespace)` for named class-like declarations as
  the first RFC scope.
- Excluded descendants, explicit root, `protected(namespace)`, `internal`,
  friend namespaces, modules, and native consistent accessibility from RFC v1.
- Rewrote `docs/wiki/05-rfc/draft.md` so Proposal contains only the selected
  minimal scope.
- Added DEC-020 through DEC-040 as v1 decision records.
- Replaced open questions with final dispositions and implementation gates.
- Reworked test matrix and PHPT plan for exact-only v1 and marked existing
  `protected(namespace)` tests as historical prototype/future-scope material.
- Aligned the C prototype with exact-only v1 by removing class-level
  `protected(namespace)` token/parser support and descendant runtime behavior.
- Normalized declaration and caller namespace strings for exact comparison.
- Updated Reflection/tokenizer metadata to expose only private namespace
  visibility in v1.
- Rewrote namespace visibility PHPTs for exact-only semantics and added
  rejection/case-normalization coverage.
- Verified Docker debug build and 13/13 targeted namespace visibility PHPT
  tests for the now-superseded exact-only model.
- Revised the selected model after bounded-context feedback: `private(namespace)`
  remains exact-only and `protected(namespace)` again means declaring namespace
  plus descendants for class-like declarations.
- Restored `protected(namespace)` parser/tokenizer/Reflection/runtime support
  in the prototype while preserving normalized namespace comparisons.
- Updated RFC draft, risk register, syntax decision, test matrix, and PHPT plan
  for the private/protected model.
- Verified Docker debug build and 12/12 targeted namespace visibility PHPT
  tests for the revised model.
