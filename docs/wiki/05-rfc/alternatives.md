# Alternatives

## Plain `private class`

Pros:

- Short syntax.
- Familiar word.
- Prior art in old namespace visibility and encapsulation drafts.

Cons:

- Conflicts with the private classes/functions draft, where `private class`
  means file or namespace-block private through name mangling.
- Harder to extend to `private(file)` or `private(module)`.
- Gives no indication that the restriction is exact namespace based.

Status: rejected for RFC v1.

## `protected(namespace)` Terminology

Pros:

- Familiar modifier word.
- Could be read as broader than `private(namespace)`.
- Matches the bounded-context subtree use case.

Cons:

- In PHP, `protected` means inheritance-based access.
- The active member namespace visibility RFC lists `protected(namespace)` as
  Future Scope for combining namespace visibility with inheritance visibility.
- Descendant namespace access is a different hierarchy and must be described
  explicitly.

Status: accepted for the current class-level draft with explicit terminology
caveat. If Internals rejects the spelling, a fallback subtree syntax must be
chosen before voting.

## Explicit Root Syntax

Examples:

```php
protected(namespace: \Acme\Billing) class B {}
```

Pros:

- Useful for namespace subtrees.
- Decouples declaration namespace from bounded-context root.

Cons:

- Requires global namespace root rules.
- Requires root validation and refactoring policy.
- Increases implementation and voting scope.

Status: Future Scope.

## `private(namespace: static)`

Pros:

- Makes the implicit current namespace default look explicit.
- Leaves room for later explicit roots.

Cons:

- `static` in PHP suggests runtime late-static binding or class context.
- Namespace visibility is lexical and does not depend on `$this`, called class,
  or runtime caller.
- `private(namespace)` already means the current declaration namespace.

Status: rejected for RFC v1.

## `internal class`

Pros:

- Familiar from C#, Kotlin, and Swift.
- Avoids overloading `protected`.
- Reads well for library internals.

Cons:

- Those languages tie `internal` to assemblies, modules, packages, or compile
  units.
- PHP namespaces are not Composer packages and not modules.
- The term "internal" already appears in Reflection for engine/extension
  declarations.

Status: reserved for future module/package design.

## Built-in Attribute

Example:

```php
#[VisibleFrom('Acme\\Billing')]
class A {}
```

Pros:

- Avoids parser-level modifier syntax.
- Could be extended with multiple roots or friends.
- Easy for tools to read once standardized.

Cons:

- Userland attributes cannot enforce engine visibility.
- A compiler-special attribute still needs parser/compiler/runtime support.
- String namespace arguments are fragile under refactoring.
- Core visibility looks optional and metadata-like.

Status: rejected for RFC v1.

## Runtime Membrane

Every method call or property access would check the actual class of the object
against the caller namespace.

Pros:

- Stronger encapsulation around restricted implementation classes.
- Prevents use of restricted objects even after escape.

Cons:

- Breaks the public interface/public factory use case.
- Adds overhead to common object operations.
- Interacts poorly with dynamic dispatch, proxies, reflection, serialization,
  and public interfaces.
- Much larger compatibility surface.

Status: rejected for RFC v1. The class-name symbol model is selected.

## Friend Namespaces

Example future idea:

```php
friend namespace App\Tests;
```

Pros:

- Useful for tests and selective collaboration.
- Similar to friend classes or qualified exports in other languages.

Cons:

- Larger design problem.
- Can weaken architectural boundaries.
- Requires policy for transitivity, aliases, inheritance, and tooling.

Status: future scope only.

## Composer Package Boundary

Use Composer package metadata as the boundary for `internal`.

Pros:

- Matches many user expectations about package internals.
- Stronger than namespace text if package identity is known.

Cons:

- Composer is not part of Zend Engine.
- PHP can run code without Composer.
- Autoloaders are userland and dynamic.
- Package identity is not present in opcodes or class entries today.

Status: out of scope for this language feature.

## Native Consistent Accessibility

Pros:

- Prevents public APIs from exposing restricted types.
- Aligns with languages that require public signatures to mention public types.

Cons:

- Expands class-linking, lazy type resolution, autoload, variance, Reflection,
  and OPcache scope.
- May force new diagnostics before all referenced types are loaded.

Status: Future Scope. RFC v1 allows declaration-site use and restricts external
semantic use.
