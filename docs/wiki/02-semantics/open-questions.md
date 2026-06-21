# Open Questions

## Semantics

- Should namespace comparison be case-insensitive and canonicalized to lowercase
  for checks, while preserving original spelling for diagnostics?
- Should `protected(namespace)` be rejected in the global namespace, or treated
  as exact global access?
- Should `SomeClass::class` be checked, or should it remain a pure string
  operation with checks deferred until class-string use?
- Should `class_exists()` and related existence probes reveal restricted
  symbols?
- Should `is_callable()` return `false` or throw for inaccessible class-string
  callables?
- How should `catch (Restricted $e)` behave when the restricted class is not
  loaded, given current no-autoload catch behavior?
- Should `instanceof Restricted` enforce class-like visibility if current PHP
  avoids autoload for literal RHS?

## Public API

- Should public APIs be forbidden from exposing restricted types?
- If yes, when can this be checked without forcing new autoload behavior?
- How should variance checks handle restricted parent/interface types?
- Can a public class extend a restricted parent if the declaration namespace is
  allowed?
- Can external code extend that public child without naming the restricted
  parent?

## Runtime and Engine

- What exact structure should carry lexical caller namespace for top-level code,
  functions, closures, methods, eval, and internal callback paths?
- Can class-like checks reuse PR #20421's `op_array->namespace_name`, or should
  they use class/function scope information differently?
- Which runtime caches need caller namespace in their key or a late check on
  cache hit?
- Should reflection construction be privileged or access-checked?
- How should unserialize choose caller namespace for restricted class payloads?

## Syntax and Scope

- Should `protected(namespace: Root)` be in the first RFC or Future Scope only?
- Should interfaces, traits, and enums all be included in the first RFC?
- Should namespace visibility later apply to functions and constants?
- Should `internal` be reserved explicitly for future modules/packages?
- How should modifiers order with `abstract`, `final`, and `readonly` be
  constrained in grammar?

## Process

- Is this better as a new RFC, or as a successor to the old
  `namespace-visibility` draft?
- How should it coordinate with the under-discussion methods/properties RFC?
- What PHP version could realistically accept an ABI-impacting class-entry
  metadata change?

