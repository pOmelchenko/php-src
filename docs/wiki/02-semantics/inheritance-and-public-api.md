# Inheritance and Public API

Namespace visibility for class-like declarations raises a consistent
accessibility question:

```php
namespace Acme\Billing;

private(namespace) class InternalType {}

public class Api {
    public function get(): InternalType {}
}
```

Can a public API expose a restricted type?

## Models

### Model 1: Fully Forbid Inconsistent Accessibility

Public declarations could not expose restricted types in signatures, inheritance
relations, public properties, or public class constants.

Pros:

- Strong API hygiene.
- Similar to languages that reject public APIs with less visible types.
- Prevents external code from receiving unusable type names in public
  signatures.

Cons:

- PHP often compiles declarations before all referenced types are autoloaded.
- Enforcing across all lazy type positions may require autoloading at times PHP
  currently avoids.
- Existing dynamic loading order could make diagnostics order-dependent.
- It is larger than the base visibility feature.

### Model 2: Allow Exposure, Check Actual Use

Public APIs may mention restricted types. External code can call public APIs,
but direct use of the restricted name is rejected when it occurs.

Pros:

- Matches PHP's lazy and dynamic nature.
- Lower implementation complexity.
- Preserves the object escape use case.

Cons:

- Public reflection and type errors may expose restricted type names.
- External code may see an API it cannot fully implement, extend, or type
  against.
- Some failures move from declaration time to use time.

### Model 3: Defer Consistent Accessibility

The first RFC defines name visibility and explicitly defers API exposure rules
to a later RFC.

Pros:

- Keeps the first prototype smaller.
- Avoids forcing early autoload or whole-program checks.
- Allows data from implementation and tooling before choosing stricter rules.

Cons:

- Leaves a visible design gap.
- Static analyzers and API authors need interim guidance.

## Current Recommendation

Defer consistent accessibility in the first prototype while documenting
exposure risks. Enforce direct name use and class-linking operations that
actually require the restricted CE. Do not claim full API consistency until
autoload and lazy type feasibility are proven.

## Cases to Cover

| Case | Recommended first-scope behavior |
| --- | --- |
| Direct inheritance from restricted base | Check at class linking using child declaration namespace. |
| Indirect inheritance through public child | Unresolved; likely allow if direct link was legal, but external extension may be blocked when restricted base must be named or linked. |
| Public interface with restricted implementation | Allow; this is a motivating use case. |
| Restricted base with public child | Risky. May expose inherited public API while hiding parent name. Needs tests and RFC discussion. |
| Variance | Type compatibility checks may need to resolve restricted names; do not force early whole-program checks in first scope. |
| Aliases | Alias does not widen visibility. A public alias to a restricted CE remains restricted. |
| Public properties | Public typed properties exposing restricted type are a consistent accessibility issue. |
| Public typed class constants | Same as public typed properties. |
| PHPDoc | Not part of language semantics. Static analyzers may warn, but the engine should not enforce PHPDoc. |

