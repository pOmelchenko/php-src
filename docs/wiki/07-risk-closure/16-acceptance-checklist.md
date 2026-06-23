# Acceptance Checklist

This checklist is for starting RFC discussion. It is not a voting checklist.
`Done` means the RFC text and focused prototype evidence are sufficient for an
implementation-backed discussion. `Focused coverage passed` means targeted PHPT
or benchmark evidence exists, but it is not a claim that the whole PHP test
suite has been cleared.

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
| Reflection policy defined | Done for discussion; one voting gap remains | [08](08-reflection-autoload-aliases.md); `ReflectionAttribute::newInstance()` timing remains a voting blocker |
| Aliases defined | Done | [08](08-reflection-autoload-aliases.md) |
| Autoload defined | Done | [08](08-reflection-autoload-aliases.md) |
| `::class` defined | Done | [08](08-reflection-autoload-aliases.md) |
| Global namespace defined | Done | [04](04-normative-semantics.md) |
| Class-fetch coverage map | Done | [07](07-runtime-and-caches.md) |
| Cache-order tests | Focused coverage passed | `new`, callable-cache, OPcache CLI, alias, and trait-body cache-sensitive focused tests |
| OPcache/preload behavior | Focused coverage passed | OPcache CLI, file cache, preload, preload linking, JIT, and trait-body metadata tests |
| Performance evidence | Focused benchmark gate passed | [11](11-performance-evidence.md): public hot-path gate and trait-body compile/link follow-up pass |
| RFC draft updated | Done | [../05-rfc/draft.md](../05-rfc/draft.md) |
| Future Scope separated | Done | RFC draft and [13](13-rfc-splitting.md) |
| No hidden security claims | Done | [10](10-compatibility-and-security.md), [15](15-accepted-risks.md) |
| Local publication hygiene | Must verify before publication | Only raw benchmark outputs may remain untracked locally; rerun `git status` before sending |

## Readiness

Ready for RFC discussion: **yes after branch publication and summary
preparation**. The focused implementation gates are reconciled and measured.

The discussion package still needs to be assembled from the reconciled docs:

- scope and syntax;
- normative semantics;
- implementation coverage;
- OPcache/preload/JIT evidence;
- performance evidence;
- accepted limitations and future scope.

Ready for RFC voting: **no** until generated artifacts, broader test-suite
coverage, and any scope adjustments from discussion are complete. Gate 5
performance is no longer the blocker in the retained microbenchmark gate.

Voting blockers:

- generated/release-clean artifacts are not finalized;
- broader PHPT coverage has not been rerun;
- `ReflectionAttribute::newInstance()` and attribute validation timing still
  need implementation coverage or explicit deferral;
- internals discussion may require scope or wording changes.
