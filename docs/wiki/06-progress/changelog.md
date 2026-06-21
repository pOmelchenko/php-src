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
- Added English RFC draft with `Author: TBD`.
- No C code, generated files, or PHPT tests were added.
- Added Docker development environment under `docker/dev`.
- Built and smoke-tested the Docker image with Bison 3.8.2, re2c 3.0, and
  Autoconf 2.71.
