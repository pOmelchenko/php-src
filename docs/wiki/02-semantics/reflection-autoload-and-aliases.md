# Reflection, Autoload, and Aliases

This page evaluates the starting hypotheses.

## `class_alias()`

Hypothesis: `class_alias()` does not remove restrictions.

Current direction: accept for prototype.

Reasoning:

- Alias names point at the same `zend_class_entry`.
- Visibility metadata should belong to the class entry, not to a particular
  spelling.
- A public alias to a restricted class would otherwise be a one-line bypass.

Open detail:

- Whether creating the alias itself requires access to the source class name.
  The safer rule is to check source class resolution using the lexical namespace
  of the `class_alias()` call.

## Existence Functions

Hypothesis: `class_exists()` may report existence.

Current direction: likely accept.

Reasoning:

- Visibility does not need to hide the existence of symbols.
- PHP reflection and class-table behavior already expose many names.
- Treating existence as access would make discovery tools and autoload probes
  brittle.

Open detail:

- `is_a()`, `is_subclass_of()`, `method_exists()`, `property_exists()`, and
  `defined('C::X')` need per-function decisions because they may resolve class
  names more deeply than simple existence checks.

## Reflection

Hypothesis: Reflection can read metadata, but object creation should enforce
visibility unless explicitly documented as privileged.

Current direction:

- `ReflectionClass` may expose name, namespace, and new metadata such as
  `isNamespacePrivate()` and `isNamespaceProtected()` if such methods are
  added.
- `ReflectionClass::newInstance()` and
  `ReflectionClass::newInstanceWithoutConstructor()` should enforce class-like
  visibility for the call-site namespace unless an RFC explicitly chooses a
  privileged bypass.

Reasoning:

- Existing reflection has privileged member access history, but class-like name
  visibility is broader.
- A reflection construction bypass would make the feature weaker than ordinary
  `new` restrictions.
- The term "internal" is already used by Reflection for PHP/extension-provided
  classes and functions, so `internal` should not be reused casually for
  namespace visibility metadata.

## `SomeClass::class`

Hypothesis: `SomeClass::class` needs a separate decision.

Current direction: unresolved.

Reasoning:

- PHP normally compiles `SomeClass::class` to a string without autoloading.
- Enforcing visibility there would change a low-cost constant expression into a
  potentially loading or metadata-dependent operation.
- Not enforcing it means external code can obtain a restricted class string, but
  subsequent use as a class name should still be rejected.

Candidate decisions:

1. allow `Restricted::class` everywhere and enforce only when the string is used;
2. reject `Restricted::class` when the target is already known and inaccessible;
3. force autoload/metadata lookup for restricted-aware `::class`, which is the
   largest behavior change.

## Autoload

Hypothesis: metadata for an unloaded class becomes known only after autoload.

Current direction: accept.

Implications:

- A forbidden `new Restricted()` may invoke autoload before throwing.
- Autoloader side effects may occur before denial.
- The caller namespace for the original operation must be preserved; the
  autoloader's namespace must not become the caller namespace for the access
  check.

## Direct `require`

Hypothesis: direct `require` of a class file does not bypass enforcement.

Current direction: accept.

Reasoning:

- Including the file registers the class entry.
- Later name use must still check the caller namespace.
- Merely loading a declaration is not permission to use it.
