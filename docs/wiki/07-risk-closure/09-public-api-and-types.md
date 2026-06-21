# Public API and Types

Example:

```php
namespace Acme;

private(namespace) class InternalResult {}

class PublicApi {
    public function result(): InternalResult {}
}
```

## Policy A: Native Consistent Accessibility

Public API cannot expose restricted types.

| Concern | Impact |
| --- | --- |
| Unloaded types | Requires eager loading or deferred diagnostics |
| Autoload | May add new autoload timing |
| Inheritance | Must inspect inherited public/protected APIs |
| Variance | Must compare visibility across parent/interface signatures |
| Union/intersection/DNF | Every component needs visibility relation checks |
| Aliases | Must canonicalize aliases to CE metadata |
| Public properties/constants | Same rule needed outside methods |
| Interface implementation | Public interface methods cannot expose private implementation types |
| Reflection | Reflection must expose diagnostics or modifiers |
| OPcache | Must cache consistency result and invalidation |

Policy A is coherent but too large for RFC v1.

## Policy B: Declaration Allowed, External Usage Restricted

The declaration above is allowed because the type name is used in namespace
`Acme`, which is allowed to name `InternalResult`. External code can call
`PublicApi::result()` and receive an object, but cannot name
`Acme\InternalResult` in its own code.

| Concern | Impact |
| --- | --- |
| Unloaded types | Existing lazy type resolution can be preserved |
| Autoload | No new global scan required |
| Inheritance | Declaration-site checks still apply when types resolve |
| Variance | Existing variance timing remains, with visibility checked when CEs resolve |
| Union/intersection/DNF | Each component is checked at declaration-site resolution |
| Aliases | Alias does not widen CE visibility |
| Public properties/constants | Allowed if declared in an allowed namespace |
| Interface implementation | Implementing declaration must be allowed to name its types |
| Reflection | Reflection may show restricted type names |
| OPcache | Needs metadata persistence, not API graph analysis |

Policy B is selected for RFC v1.

## Policy C: No Native Restriction in v1

This would mean type declarations are not class-name semantic use at all in v1.
It minimizes implementation but contradicts the symbol visibility invariant and
leaves obvious external type-name usage unrestricted.

Policy C is rejected for v1.

## Decision

RFC v1 does not implement native consistent accessibility. It does check
restricted class-like names when type declarations resolve to a CE. Public APIs
may expose restricted types if the exposing declaration is itself in an allowed
namespace.

Static analyzers should warn on public API exposure as an architectural leak.
That recommendation is not part of the language semantics.

