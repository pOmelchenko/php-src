# RFC Splitting

## RFC A: Class-level Namespace Visibility

Scope:

```php
private(namespace) class-like declarations
protected(namespace) class-like declarations
```

Excludes explicit root, friend namespaces, modules, native consistent
accessibility, and privileged Reflection bypass.

| Item | Value |
| --- | --- |
| Standalone value | Enforces exact namespace internals and bounded-context namespace trees |
| Dependency | None, but should coordinate terminology with member RFC |
| Voting question | Add `private(namespace)` exact and `protected(namespace)` subtree visibility for named class-like declarations? |
| Implementation dependency | Parser/metadata/class-fetch/type/Reflection/OPcache gates |
| Migration path | Add modifier to existing internal class-like declarations |
| Future BC conflict | Rejects explicit root syntax so root forms remain available |

## RFC B: Alternative Subtree Syntax Fallback

Fallback if Internals rejects `protected(namespace)` terminology for subtree
visibility.

| Item | Value |
| --- | --- |
| Standalone value | Preserves bounded-context use case without `protected(namespace)` |
| Dependency | RFC A discussion outcome |
| Voting question | Replace or supplement `protected(namespace)` with selected subtree syntax? |
| Implementation dependency | Segment-aware comparison, global-root decision, extra tests |
| Migration path | Broaden selected exact declarations where needed |
| Future BC conflict | Must be settled before final RFC A vote if replacing syntax |

## RFC C: Explicit Ancestor Root

Allows declaration to name an ancestor root, similar to D `package(root)` or
Rust `pub(in ancestor)`.

| Item | Value |
| --- | --- |
| Standalone value | Decouples declaration namespace from allowed ancestor |
| Dependency | RFC A |
| Voting question | Add explicit namespace root syntax for namespace visibility? |
| Implementation dependency | Parser root expression, validation that root is self or ancestor |
| Migration path | Broaden exact/subtree declarations intentionally |
| Future BC conflict | None if `private(namespace: ...)` and `protected(namespace: ...)` are rejected in RFC A |

## RFC D: Modules/Internal

Defines real package/module boundary and an `internal`-style visibility.

| Item | Value |
| --- | --- |
| Standalone value | True ownership and package/module semantics |
| Dependency | None semantically; may reuse lessons |
| Voting question | Add modules/package boundary and internal visibility? |
| Implementation dependency | Module identity, loading, autoload/preload integration |
| Migration path | Libraries can move from namespace convention to module boundary |
| Future BC conflict | Avoided by not using `internal` in RFC A |
