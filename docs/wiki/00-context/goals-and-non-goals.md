# Goals and Non-goals

## Goals

- Define namespace-scoped visibility for class-like declarations as a working
  hypothesis, not as an accepted PHP decision.
- Preserve existing public semantics when no new modifier is present.
- Prefer a syntax that is explicit about namespace visibility and does not
  confuse inheritance-based `protected` member visibility.
- Make checks depend on lexical caller namespace, not on `debug_backtrace()`,
  current object state, autoload order, or whether a class was previously
  loaded by allowed code.
- Support class, interface, trait, and enum declarations in the intended model.
- Keep anonymous classes out of the first model because they do not have a
  stable top-level name to protect.
- Treat restrictions as restrictions on use of class-like names, not as a
  runtime membrane around every object operation.
- Allow public factories to return public interfaces implemented by restricted
  concrete classes.
- Identify all php-src hotspots before writing C code.
- Keep explicit root syntax in Future Scope until the base semantics are
  stable.
- Produce a PHPT plan before implementation.

## Non-goals

- Do not treat namespaces as a security sandbox.
- Do not equate PHP namespaces with Composer packages.
- Do not add an `internal` modifier in the first prototype without a real module
  or package boundary.
- Do not introduce friend namespaces or multiple allowed roots in the first
  prototype.
- Do not use an INI flag to hide incomplete enforcement.
- Do not publish an RFC or modify the PHP Wiki from this repository.
- Do not create public Zend API without a separate ABI/API analysis.
- Do not mass-format or refactor unrelated engine code.

## Prototype Boundary

The first possible C-code prototype should be a parser and metadata spike only
after:

- this repository is confirmed as php-src;
- exact parser/compiler/runtime locations are mapped;
- minimal PHPT tests exist;
- the build environment can compile generated parser/scanner artifacts or the
  work avoids regenerating them.

In the current local environment, no C prototype was started because the
worktree is not configured, the CLI binary is absent, `re2c` is missing, and the
available Bison is old.

