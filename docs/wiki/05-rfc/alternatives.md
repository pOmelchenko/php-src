# Alternatives

## Plain `private class` and `protected class`

Pros:

- Short syntax.
- Familiar visibility words.
- Prior art in the old PHP namespace visibility draft.

Cons:

- Ambiguous with member visibility.
- `protected class` can be read as subclass visibility.
- `private class` conflicts with possible file-private visibility.
- Harder to extend to `private(file)` or `private(module)`.

Status: rejected for first prototype.

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

Status: not preferred for base syntax.

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

Status: rejected for first prototype. The name model is preferred.

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

