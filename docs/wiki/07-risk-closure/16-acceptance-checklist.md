# Acceptance Checklist

| Criterion | Status | Evidence |
| --- | --- | --- |
| Every risk has final disposition | Done | [01-risk-register.md](01-risk-register.md): no undecided or BLOCKED risk remains |
| Minimal first RFC selected | Done | Revised scope in [02](02-minimal-scope.md) |
| Syntax conflict handled | Done | [03](03-syntax-decision.md) |
| Single access invariant | Done | [05](05-operation-coverage.md) |
| Descendants/root defined or deferred | Done | [13](13-rfc-splitting.md) |
| `protected(namespace)` terminology handled | Mitigated | [03](03-syntax-decision.md), RISK-001 |
| Types defined | Done | [09](09-public-api-and-types.md) |
| Traits defined | Done | [06](06-lexical-scope.md) |
| Reflection defined | Done | [08](08-reflection-autoload-aliases.md) |
| Aliases defined | Done | [08](08-reflection-autoload-aliases.md) |
| Autoload defined | Done | [08](08-reflection-autoload-aliases.md) |
| `::class` defined | Done | [08](08-reflection-autoload-aliases.md) |
| Global namespace defined | Done | [04](04-normative-semantics.md) |
| Class-fetch coverage map | Done | [07](07-runtime-and-caches.md) |
| Cache-order tests | Focused coverage passed | `new`, callable-cache, OPcache CLI, alias, and trait-body cache-sensitive focused tests |
| OPcache/preload plan or results | Measured, passed for focused coverage | OPcache CLI, file cache, preload, preload linking, JIT, and trait-body metadata tests |
| Performance evidence | Measured, passed for retained microbenchmarks | [11](11-performance-evidence.md): public hot-path gate and trait-body compile/link follow-up pass |
| RFC draft updated | Done | [../05-rfc/draft.md](../05-rfc/draft.md) |
| Future Scope separated | Done | RFC draft and [13](13-rfc-splitting.md) |
| No hidden security claims | Done | [10](10-compatibility-and-security.md), [15](15-accepted-risks.md) |
| No unrelated git diff | Must verify before publication | Current raw benchmark outputs may remain untracked locally |

## Readiness

Ready for RFC discussion: **yes for an implementation-backed discussion
package**, after publishing the branch and sending a concise summary. The
focused implementation gates are reconciled and measured.

Ready for RFC voting: **no** until generated artifacts, broader test-suite
coverage, and any scope adjustments from discussion are complete. Gate 5
performance is no longer the blocker in the retained microbenchmark gate.
