# RFC Splitting

## RFC A: Exact Namespace Class-level Visibility

Scope:

```php
private(namespace) class-like declarations
```

Excludes descendants, explicit root, friend namespaces, modules, native
consistent accessibility, and privileged Reflection bypass.

| Item | Value |
| --- | --- |
| Standalone value | Enforces exact namespace internals across files |
| Dependency | None, but should coordinate terminology with member RFC |
| Voting question | Add `private(namespace)` exact namespace visibility for named class-like declarations? |
| Implementation dependency | Parser/metadata/class-fetch/type/Reflection/OPcache gates |
| Migration path | Add modifier to existing internal class-like declarations |
| Future BC conflict | Rejects extra syntax so descendant/root forms remain available |

## RFC B: Namespace Subtree Visibility

Adds a descendant/subtree mode with a syntax not preselected as
`protected(namespace)`.

| Item | Value |
| --- | --- |
| Standalone value | Allows `Acme\Billing` to expose to `Acme\Billing\*` |
| Dependency | RFC A exact model and CE metadata |
| Voting question | Add namespace subtree visibility, with selected syntax? |
| Implementation dependency | Segment-aware comparison, global-root decision, extra tests |
| Migration path | Broaden selected exact declarations where needed |
| Future BC conflict | No conflict if RFC A rejects subtree syntax |

## RFC C: Explicit Ancestor Root

Allows declaration to name an ancestor root, similar to D `package(root)` or
Rust `pub(in ancestor)`.

| Item | Value |
| --- | --- |
| Standalone value | Decouples declaration namespace from allowed ancestor |
| Dependency | RFC A; optionally RFC B |
| Voting question | Add explicit namespace root syntax for namespace visibility? |
| Implementation dependency | Parser root expression, validation that root is self or ancestor |
| Migration path | Broaden exact/subtree declarations intentionally |
| Future BC conflict | None if `private(namespace: ...)` rejected in RFC A |

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

