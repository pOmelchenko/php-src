# Open Questions

For the exact-only RFC v1 model, no semantic question remains unresolved. Items
from the first phase are now either resolved, deferred, accepted, or assigned to
an implementation gate.

## Closed for RFC v1

| Question | Disposition | Decision |
| --- | --- | --- |
| Namespace comparison case handling | RESOLVED | Normalize using class-like lookup case semantics |
| Global namespace | RESOLVED | Empty string exact namespace |
| `SomeClass::class` | RESOLVED | String production, no access check or autoload |
| Existence probes | RESOLVED | May reveal existence, no capability |
| Reflection construction | RESOLVED | Metadata allowed; construction checked |
| Alias behavior | RESOLVED | CE metadata preserved |
| Error timing | RESOLVED | Check after target CE resolution |
| Runtime membrane | RESOLVED | Not an object membrane |

## Deferred from RFC v1

| Question | Disposition | Future work |
| --- | --- | --- |
| Descendant namespace visibility | DEFERRED | RFC B |
| Explicit root syntax | DEFERRED | RFC C |
| `protected(namespace)` meaning | DEFERRED | Separate inheritance/descendant syntax decision |
| Native consistent accessibility | DEFERRED | Separate RFC after type/autoload analysis |
| Module/package `internal` | DEFERRED | Module/package RFC |
| Friend namespaces | DEFERRED | Friend/package visibility RFC |

## Implementation Gates

The following are not semantic open questions. They are implementation gates:

- carry lexical namespace through top-level code, closures, arrow functions,
  eval, class linking, internal functions, and Reflection;
- preserve trait declaration namespace for operations written in trait bodies;
- check all class-entry cache hit paths;
- validate OPcache/preload/JIT parity;
- measure public fast-path overhead.

See [../07-risk-closure/14-implementation-gates.md](../07-risk-closure/14-implementation-gates.md).
