# Performance

Gate 5 microbenchmarks were run for the namespace-visibility prototype. The
current implementation passes the retained public hot-path Gate 5 threshold in
the latest paired container run. The largest public cache-hit regressions were
found and fixed.

Full measurements, commands, and raw artifact names are in
[11-performance-evidence.md](../07-risk-closure/11-performance-evidence.md).

## Data Size

Measured release NTS container builds:

| Structure | Baseline `0fff3ccce2f` | Current `6391d08d0cb` | Delta |
| --- | ---: | ---: | ---: |
| `sizeof(zend_class_entry)` | 528 | 528 | 0 |
| `sizeof(zend_op_array)` | 256 | 280 | +24 |
| `sizeof(zend_op_array_namespace_range)` | n/a | 16 | +16 current-only |

`zend_class_entry::ce_flags2` already exists in the baseline tree. The prototype
no longer stores the namespace-visibility root on every class entry; restricted
classes derive their lowercase root from `zend_class_entry::name` only on the
restricted slow path. The remaining op-array growth comes from:

- `zend_op_array::lexical_namespace`;
- `zend_op_array::last_namespace_range`;
- `zend_op_array::namespace_ranges`.

The namespace range array is allocated only for main op arrays that need
per-opline namespace block mapping.

## OPcache Memory

The benchmark generated 10000 classes and compiled them with
`opcache_compile_file()` under OPcache CLI without JIT.

| Build | Fixture | Used memory delta | Bytes/class | Interned bytes/class |
| --- | --- | ---: | ---: | ---: |
| baseline | public | 12451688 | 1245.1688 | 238.2024 |
| current | public | 12451728 | 1245.1728 | 238.2024 |
| current | private restricted | 12451728 | 1245.1728 | 239.7672 |
| current | protected restricted | 12451728 | 1245.1728 | 239.9944 |

The previous ~16.0 bytes/class non-interned OPcache memory delta disappeared
after removing the stored class-entry namespace root. Restricted fixtures now
match current public classes for non-interned used memory and add about 1.6 to
1.8 interned bytes/class from generated restricted source text and names.

## Runtime Cost

The intended unrestricted fast path is a CE flag check:

```c
if (!(ce->ce_flags2 & ZEND_ACC2_NAMESPACE_RESTRICTED)) {
    return true;
}
```

The retained Gate 5 optimization keeps this check off several public cache-hit
paths:

- the current lexical namespace is fetched only for namespace-restricted CEs;
- static property result caches, class constant value caches, and static method
  function caches skip repeated visibility checks after a successful cache
  population;
- restricted CEs are not stored in those shared cache slots when the executing
  op array has top-level namespace ranges;
- the JIT static-method helper uses the same cache-population rule.

This fixed the earlier large public regressions in static property access and
callable validation. A later `public_instanceof` regression was traced to VM
layout: the inline const-class miss path forced an extra callee-saved register
spill in the AArch64 hot handler. Moving that miss path to a cold VM helper
removed the spill. Those old figures are no longer representative of the
current source.

The latest long paired `public_instanceof` rerun is under the accepted `<= 3%`
threshold in all modes:

| Mode | Measurement | Delta | Status |
| --- | --- | ---: | --- |
| no OPcache | `public_instanceof` | -0.49% | Pass |
| OPcache, no JIT | `public_instanceof` | -5.01% | Pass |
| OPcache, function JIT | `public_instanceof` | +0.14% | Pass/noisy |
| OPcache, tracing JIT | `public_instanceof` | +2.20% | Pass/noisy |

Callgrind in the container shows no meaningful interpreted instruction growth
for OPcache/no-JIT public `new` (+0.20% total Ir). For public `instanceof`, the
after-fix profile is -1.09% total Ir and the hot handler drops by about two
instructions per iteration after the miss helper split.

## Runtime Cache

Runtime cache entries cannot simply skip checks for all cached CEs: a restricted
class may be first used from an allowed namespace and later from a denied one.
The retained compromise is:

- public CEs can use the normal shared hot cache;
- restricted CEs use shared cache entries only when the executing op array has
  no namespace ranges and the successful check is stable for that code;
- otherwise the operation falls back to rechecking rather than widening access.

## OPcache and Preload

OPcache correctness is validated separately in Gate 4. Gate 5 measured OPcache
runtime modes and memory impact, but not representative applications.

Preload memory and invalidation cost were not separately benchmarked beyond the
targeted OPcache memory microbenchmark above.

## Remaining Work

Before RFC voting:

- keep the after-fix Gate 5 numbers with the RFC materials;
- add representative application benchmarks only after the public microbench
  gate is closed.
