# Adversarial Review

| # | Objection | Strongest form | Factual basis | Response | Residual risk | RFC change | Status |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | Leave it to PHPStan/Psalm | Static tools already model `@internal` without engine cost | Many projects use analyzers | Engine enforcement catches dynamic runtime use and library consumers without analyzer parity | Tooling still valuable | Add analyzer recommendation only | MITIGATED |
| 2 | Namespace is not a boundary | PHP namespaces are names, not ownership | Any file can declare any namespace | Correct; RFC claims architecture, not security | Intentional spoofing works | Add security non-goal | ACCEPTED |
| 3 | Namespace can be forged | Malicious code can choose `Vendor\Package` | Language permits it | Same as above; feature is for cooperating code | No untrusted isolation | Explicit warning | ACCEPTED |
| 4 | Wait for modules | Modules would solve real package boundaries | Externals module threads | Modules are larger and unresolved; exact namespace visibility is useful now | Future modules may supersede `internal` | Reserve `internal` | MITIGATED |
| 5 | Use file-private classes | File-private avoids namespace spoofing and runtime overhead | Private classes/functions draft | Different use case: helpers shared across files in one namespace | Two features may coexist | Reject plain `private class` | RESOLVED |
| 6 | Use attributes | Attributes avoid grammar changes | `#[VisibleFrom]` can carry metadata | Userland attributes cannot enforce CE use; engine-special attributes still need runtime checks | Attribute syntax is extensible | Keep as rejected alternative | RESOLVED |
| 7 | Runtime overhead | Every class lookup may pay | Many hot paths resolve CEs | RFC requires public fast path and benchmarks before voting | Not measured | Gate 5 required | MITIGATED |
| 8 | Exact namespace is too narrow | Real projects use `Internal\Sub` trees | Common namespace organization | Exact-only is predictable and avoids new hierarchy semantics; subtree RFC can follow | Less convenient | Future Scope RFC B | DEFERRED |
| 9 | Public signatures leak restricted types | Users see types they cannot name | Reflection and declarations expose names | v1 allows declaration-site use and restricts external semantic use; native consistency is separate | API awkwardness | Future consistent accessibility RFC | DEFERRED |
| 10 | Reflection bypass | Reflection can inspect and instantiate | Reflection has special powers | Introspection is allowed; instantiation is checked; no privileged bypass in v1 | Names visible | Normative Reflection section | RESOLVED |
| 11 | `::class` allowed | It leaks restricted names | PHP returns strings without autoload | Strings are not hidden; later semantic use checks | Class strings visible | Explicit `::class` rule | RESOLVED |
| 12 | Autoload before error | Forbidden use can run arbitrary code | Metadata only after loading | Accepted limitation unless manifest exists | Side effects remain | Autoload order section | ACCEPTED |
| 13 | Plain `private class` is shorter | Better readability | Old RFCs used it | It conflicts with file-private name-mangling draft | More verbose syntax | Syntax decision | RESOLVED |
| 14 | Descendants excluded | Exact-only omits major use case | Namespace trees common | Descendants need syntax, segment rules, root/global rules, vote | Future pressure | RFC splitting | DEFERRED |
| 15 | Wait for member-level RFC | Shared syntax may change | Active member RFC under discussion | Coordinate terminology, but class-like names are a different operation surface | Syntax changes upstream could affect draft | Source register and discussion readiness gate | MITIGATED |

