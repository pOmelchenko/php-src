# Performance Evidence

Performance evidence is **MEASURED** for the selected
`private(namespace)`/`protected(namespace)` prototype. Gate 5 is **passed** for
the retained public hot-path microbenchmarks in the latest paired container run.

The public hot-path threshold is: median regression must be `<= 3%` or clearly
inside measured noise. The first retained measurement exposed real public
cache-hit regressions. Those were traced to namespace-visibility checks left on
cached static-property, static-method, class-constant, and JIT static-method
paths. The current source moves those checks to cache population for public-safe
caches and keeps restricted CEs out of shared cache hits when the executing op
array has top-level namespace ranges.

That fixed the large public static/cache regressions. This iteration also
removed the stored namespace root from `zend_class_entry`; the restricted slow
path now derives the lowercase root from the class name. That restores
`sizeof(zend_class_entry)` to the baseline value and removes the measured
non-interned OPcache memory delta.

The remaining `public_instanceof` wall-clock regression was traced to VM layout:
the inline const-class miss path forced an extra callee-saved register spill in
the AArch64 `ZEND_INSTANCEOF_SPEC_CV_CONST_HANDLER` hot path. Moving lookup,
namespace-visibility checking, and cache population for the const-class miss
into a cold helper removed that spill. The after-fix paired run keeps
`public_instanceof` under the accepted `<= 3%` public hot-path threshold in all
four measured modes.

Raw artifacts were kept outside the repository under
`/tmp/nsvis-gate5-results/`.

## Artifacts

| Artifact | Purpose |
| --- | --- |
| `baseline-benchmark.json` | Baseline full matrix, `0fff3ccce2f` |
| `baseline-rerun-benchmark.json` | Same-source baseline repeat to estimate drift |
| `current-final-rerun-benchmark.json` | Current full matrix after cache-population optimization |
| `current-sizefix-rerun` session output | Current full matrix after class-entry storage removal |
| `paired-public-finalall-*.json` | Alternating baseline/current paired public matrix |
| `paired-long-*.json` | Longer paired reruns for sub-10 ns rows |
| `paired-long-sizefix-public-instanceof` session output | Long paired public `instanceof` rerun after class-entry storage removal |
| `paired-afterfix-public-instanceof-20260621-pairs/*` | Seven-pair public `instanceof` rerun after the cold miss helper |
| `callgrind-*-opcache-nojit-{new,instanceof}.*` | Container Callgrind profiles for two suspicious OPcache/no-JIT rows |
| `baseline-sizes.json`, `current-final-inline-restore-sizes.json` | Structure sizes |
| `baseline-php-version.txt`, `current-final-inline-restore-php-version.txt` | Release binary versions |
| `*-configure.log`, `*-make.log` | Build logs |

Rejected experiments were also saved (`current-cachehit-global*`,
`current-globalflag*`). They are not retained: the executor-global
restricted-class flag worsened `instanceof`.

## Benchmark Record

| Field | Value |
| --- | --- |
| Date | 2026-06-21 |
| Baseline commit | `0fff3ccce2f5f9e0695502a509fa8e8edf8f77d4` |
| Current commit | `6391d08d0cbc4f25d38e9ac60fe7873b0a47e785` plus local Gate 5 optimization, class-entry storage removal, `instanceof` cold miss helper, and docs edits |
| Container | `php-src-dev:bookworm` from `docker/dev/compose.yml` |
| OS/CPU | Docker LinuxKit `6.12.76-linuxkit`, `aarch64`, 14 vCPU visible in container |
| Compiler | `cc (Debian 12.2.0-14+deb12u1) 12.2.0` |
| Build configuration | release NTS CLI, `CFLAGS="-O2 -g0" ./configure --disable-all --enable-cli --without-pear` |
| OPcache build note | The built CLI reports `with Zend OPcache v8.6.0-dev`; OPcache/JIT modes were enabled with per-run `-d` settings |
| Main runs | `NSVIS_RUNS=7`, `NSVIS_WARMUP=2`, `NSVIS_ITERS=1000000`, `NSVIS_DENIED_ITERS=10000`, autoload hit `20000` |
| Paired runs | 7 or 9 alternating baseline/current workers, each worker 3 measured runs after 1 warmup |
| Long paired runs | Higher iterations for sub-10 ns rows, listed below |
| Benchmark harness | `benchmark/ns_visibility_gate5.php` |
| Structure size helper | `benchmark/ns_visibility_sizes.c` |

Representative command shape:

```sh
rtk docker compose -f docker/dev/compose.yml run --rm \
  -v /tmp/nsvis-gate5-results:/results \
  -v /tmp/nsvis-gate5-builds:/builds \
  php-src-dev bash -lc '
work=/builds/php-src-nsvis-current-1782065326
cd "$work"
make -j8
env NSVIS_RUNS=7 NSVIS_WARMUP=2 NSVIS_ITERS=1000000 \
  NSVIS_DENIED_ITERS=10000 \
  ./sapi/cli/php benchmark/ns_visibility_gate5.php \
  > /results/current-final-rerun-benchmark.json
'
```

## Optimization Finding

The initial measurement that prompted Gate 5 investigation had clear threshold
violations:

| Mode | Measurement | Regression |
| --- | --- | ---: |
| no OPcache | `public_static_property` | +36.47% |
| OPcache, no JIT | `public_static_property` | +64.33% |
| OPcache, no JIT | `public_callable_validation` | +87.58% |
| OPcache, tracing JIT | `public_instanceof` | +9.27% |

The static-property and callable regressions were caused by checking namespace
visibility on public cache hits. The retained fix:

- evaluates the lexical caller namespace only when `ce_flags2` says the target
  CE is namespace-restricted;
- writes public/static caches only after a successful visibility decision;
- avoids caching restricted CEs in shared cache slots when the executing op
  array has namespace ranges;
- applies the same cache-population rule to the JIT static-method helper.

Correctness smoke after the optimization passed 27/27 focused tests, including
OPcache, preload, function JIT, tracing JIT, and namespace-range tests.

## Class-Entry Storage Removal

The follow-up optimization removed the per-class
`namespace_visibility_namespace` pointer and derives the lowercase namespace
root from `zend_class_entry::name` only after the restricted-bit guard has
failed. Reflection still returns the same lowercase root, or `null` for
unrestricted classes.

Correctness after this change passed the same 27/27 focused test set. The
release NTS size probe reported:

```json
{
  "sizeof_zend_class_entry": 528,
  "sizeof_zend_op_array": 280,
  "sizeof_zend_op_array_namespace_range": 16,
  "namespace_visibility_supported": true
}
```

## Instanceof VM Layout Fix

The class-entry storage removal did not explain the remaining
`public_instanceof` wall-clock result. Callgrind initially showed nearly
identical instruction counts, but disassembly showed that the inline const-class
miss path made the hot handler save and restore an extra callee-saved register
on AArch64. The current source moves the miss lookup, namespace-visibility
check, and cache population to `zend_instanceof_const_class_miss_helper`, a cold
VM helper reached only when the cache slot is empty.

After regeneration, the release CLI disassembly for
`ZEND_INSTANCEOF_SPEC_CV_CONST_HANDLER` no longer contains the `x19`/`x20` pair
spill in the hot prologue. The cache-hit path now loads the cached CE and calls
`instanceof_function()` without the miss-path register pressure.

## Public Paired Matrix

This table uses `paired-public-finalall-*.json`: seven alternating
baseline/current pairs, each worker doing three measured runs after one warmup.
Medians are nanoseconds per iteration. RSD columns are the standard deviation of
the seven worker medians divided by their mean.

| Mode | Measurement | Baseline | Current | Delta | RSD base/current | Result |
| --- | --- | ---: | ---: | ---: | ---: | --- |
| no OPcache | `public_new` | 30.359 | 30.661 | +1.00% | 1.98% / 0.65% | Pass |
| no OPcache | `public_static_method` | 13.187 | 13.229 | +0.32% | 0.82% / 0.94% | Pass |
| no OPcache | `public_static_property` | 6.507 | 6.554 | +0.72% | 3.00% / 4.28% | Pass |
| no OPcache | `public_class_constant` | 3.461 | 3.535 | +2.15% | 7.56% / 4.72% | Pass |
| no OPcache | `public_instanceof` | 6.460 | 6.733 | +4.22% | 2.37% / 1.42% | Needs long rerun |
| no OPcache | `public_dynamic_lookup_warm` | 22.893 | 22.683 | -0.92% | 0.89% / 1.44% | Pass |
| no OPcache | `public_callable_validation` | 46.195 | 46.616 | +0.91% | 1.11% / 17.32% | Pass |
| no OPcache | `public_autoload_hit` | 190.640 | 191.723 | +0.57% | 1.20% / 1.53% | Pass |
| OPcache, no JIT | `public_new` | 27.106 | 27.630 | +1.93% | 0.92% / 1.08% | Pass |
| OPcache, no JIT | `public_static_method` | 1.825 | 1.817 | -0.44% | 6.22% / 3.23% | Pass |
| OPcache, no JIT | `public_static_property` | 3.436 | 3.383 | -1.55% | 3.75% / 2.56% | Pass |
| OPcache, no JIT | `public_class_constant` | 1.830 | 1.821 | -0.45% | 2.72% / 3.63% | Pass |
| OPcache, no JIT | `public_instanceof` | 3.527 | 3.802 | +7.79% | 4.05% / 1.81% | Needs long rerun |
| OPcache, no JIT | `public_dynamic_lookup_warm` | 7.507 | 7.681 | +2.33% | 1.36% / 1.53% | Pass |
| OPcache, no JIT | `public_callable_validation` | 23.488 | 23.414 | -0.31% | 1.02% / 1.86% | Pass |
| OPcache, no JIT | `public_autoload_hit` | 165.629 | 166.375 | +0.45% | 1.41% / 1.92% | Pass |
| OPcache, function JIT | `public_new` | 25.571 | 25.709 | +0.54% | 1.56% / 1.22% | Pass |
| OPcache, function JIT | `public_static_method` | 0.544 | 0.536 | -1.49% | 6.85% / 7.33% | Pass |
| OPcache, function JIT | `public_static_property` | 1.013 | 0.954 | -5.82% | 1.07% / 3.10% | Pass |
| OPcache, function JIT | `public_class_constant` | 0.507 | 0.514 | +1.46% | 5.24% / 4.30% | Pass |
| OPcache, function JIT | `public_instanceof` | 2.894 | 2.988 | +3.26% | 1.98% / 2.58% | Needs long rerun |
| OPcache, function JIT | `public_dynamic_lookup_warm` | 4.215 | 4.299 | +2.00% | 1.57% / 3.58% | Pass |
| OPcache, function JIT | `public_callable_validation` | 22.053 | 22.171 | +0.53% | 0.94% / 1.14% | Pass |
| OPcache, function JIT | `public_autoload_hit` | 153.246 | 156.925 | +2.40% | 1.34% / 1.60% | Pass |
| OPcache, tracing JIT | `public_new` | 23.209 | 22.822 | -1.67% | 1.70% / 0.85% | Pass |
| OPcache, tracing JIT | `public_static_method` | 0.333 | 0.306 | -7.99% | 4.92% / 3.57% | Pass |
| OPcache, tracing JIT | `public_static_property` | 0.581 | 0.571 | -1.71% | 5.59% / 2.54% | Pass |
| OPcache, tracing JIT | `public_class_constant` | 0.306 | 0.335 | +9.56% | 6.88% / 7.92% | Needs long rerun |
| OPcache, tracing JIT | `public_instanceof` | 2.376 | 2.572 | +8.25% | 8.77% / 2.30% | Needs long rerun |
| OPcache, tracing JIT | `public_dynamic_lookup_warm` | 3.723 | 3.660 | -1.69% | 1.84% / 3.68% | Pass |
| OPcache, tracing JIT | `public_callable_validation` | 19.812 | 19.997 | +0.93% | 1.48% / 9.63% | Pass |
| OPcache, tracing JIT | `public_autoload_hit` | 154.450 | 162.150 | +4.99% | 2.31% / 2.10% | Needs long rerun |

## Long Paired Reruns

The rows above that operate in only a few nanoseconds per iteration were rerun
with higher iteration counts.

| Mode | Measurement | Iterations | Baseline | Current | Delta | RSD base/current | Result |
| --- | --- | ---: | ---: | ---: | ---: | ---: | --- |
| no OPcache | `public_instanceof` | 20000000 | 6.674 | 6.642 | -0.49% | 0.82% / 1.18% | Pass |
| OPcache, no JIT | `public_instanceof` | 20000000 | 3.622 | 3.440 | -5.01% | 2.20% / 2.71% | Pass |
| OPcache, function JIT | `public_instanceof` | 20000000 | 2.985 | 2.989 | +0.14% | 2.35% / 7.17% | Pass/noisy |
| OPcache, tracing JIT | `public_class_constant` | 300000000 | 0.310 | 0.311 | +0.34% | 4.17% / 4.25% | Pass |
| OPcache, tracing JIT | `public_instanceof` | 20000000 | 2.536 | 2.592 | +2.20% | 8.42% / 7.20% | Pass/noisy |
| OPcache, tracing JIT | `public_autoload_hit` | 100000 | 144.893 | 145.131 | +0.16% | 1.52% / 1.01% | Pass |

The `public_instanceof` rows use
`paired-afterfix-public-instanceof-20260621-pairs/*`: seven alternating
baseline/current workers per mode, each worker doing nine measured runs after
three warmups. Single-worker smoke runs can still flip sign in Docker, so the
accepted comparison is the median of worker medians. The JIT rows remain noisy,
but the medians are within the accepted public hot-path threshold.

## Callgrind Check

Callgrind was available in the container. `perf` was not used. The profiles were
run for two OPcache/no-JIT rows that had shown wall-clock regressions.

| Case | Baseline Ir | Current Ir | Delta | Hot handler result |
| --- | ---: | ---: | ---: | --- |
| `public_new`, 100000 iterations | 58,466,761 | 58,584,078 | +0.20% | top handler unchanged at 11,000,027 Ir |
| `public_instanceof`, 200000 iterations | 35,566,664 | 35,177,570 | -1.09% | `ZEND_INSTANCEOF_SPEC_CV_CONST_HANDLER` reduced from 18,799,932 to 18,399,933 Ir |

Before the cold helper, the current `public_instanceof` profile was 35,583,899
Ir with the hot handler unchanged at 18,799,932 Ir. The instruction-count tie
pointed away from class-entry storage and toward code layout. The disassembly
then exposed the extra callee-saved register spill; after the helper split,
Callgrind shows about two fewer hot-handler instructions per iteration.

## Restricted Current Cases

Current-only medians from `current-final-rerun-benchmark.json` are nanoseconds
per iteration.

| Measurement | no OPcache | OPcache, no JIT | Function JIT | Tracing JIT |
| --- | ---: | ---: | ---: | ---: |
| private allowed `new` | 30.091 | 29.352 | 25.663 | 23.821 |
| private allowed static method | 13.093 | 1.763 | 0.530 | 0.372 |
| private allowed static property | 6.491 | 3.383 | 0.973 | 0.641 |
| private allowed class constant | 3.329 | 1.773 | 0.515 | 0.363 |
| private allowed `instanceof` | 7.207 | 3.642 | 3.206 | 2.829 |
| private allowed callable validation | 87.403 | 55.596 | 54.958 | 51.667 |
| protected child allowed `new` | 31.368 | 28.683 | 26.444 | 23.546 |
| protected child allowed static method | 13.500 | 1.744 | 0.538 | 0.323 |
| protected child allowed `instanceof` | 6.701 | 3.857 | 3.359 | 2.811 |
| private denied `new` | 542.188 | 472.262 | 463.500 | 482.983 |
| private denied static method | 533.950 | 478.100 | 505.429 | 480.663 |
| private denied `instanceof` | 517.312 | 475.342 | 462.175 | 477.858 |
| protected denied `new` | 503.479 | 458.379 | 472.204 | 469.704 |

Denied cases use `NSVIS_DENIED_ITERS=10000` and measure the error path
separately from the public/allowed hot paths.

## OPcache Memory

Generated files declared 10000 classes and were compiled through
`opcache_compile_file()` with OPcache CLI enabled and JIT disabled.

| Build | Fixture | Used memory delta | Bytes/class | Interned delta | Interned bytes/class |
| --- | --- | ---: | ---: | ---: | ---: |
| baseline | public | 12451688 | 1245.1688 | 2382024 | 238.2024 |
| current | public | 12451728 | 1245.1728 | 2382024 | 238.202 |
| current | private restricted | 12451728 | 1245.1728 | 2397672 | 239.767 |
| current | protected restricted | 12451728 | 1245.1728 | 2399944 | 239.994 |

Current public classes now match the baseline public fixture within 40 bytes
across 10000 generated classes. Restricted class fixtures do not increase
non-interned used memory over current public classes, but do add about 1.56 to
1.79 interned bytes/class in this generated workload.

## Structure Sizes

| Structure | Baseline | Current | Delta |
| --- | ---: | ---: | ---: |
| `sizeof(zend_class_entry)` | 528 | 528 | 0 |
| `sizeof(zend_op_array)` | 256 | 280 | +24 |
| `sizeof(zend_op_array_namespace_range)` | n/a | 16 | +16 current-only |

`zend_class_entry::ce_flags2` already exists in the baseline build. Removing the
stored namespace root eliminated the class-entry size increase. The op-array
increase comes from lexical namespace/range metadata.

## Required Measurements

| Measurement | Status |
| --- | --- |
| Repeated instantiation public class | Measured; paired rows pass |
| Repeated static access public class | Measured; large cache-hit regressions fixed |
| Repeated `instanceof` public class | Measured; after-fix long paired rows pass |
| Allowed access restricted class | Measured on current |
| Class lookup after cache warmup | Measured; paired row passes |
| Autoloaded access | Measured; long rerun passes |
| Denied restricted access | Measured separately with low-iteration error-path loop |
| OPcache memory impact | Measured |
| `sizeof(zend_class_entry)` | Measured |
| `sizeof(zend_op_array)` | Measured |
| `sizeof(zend_op_array_namespace_range)` | Measured on current |

## Conclusion

Gate 5 is **passed** for the retained public hot-path benchmark gate. The major
public cache-hit regressions were found and fixed, the class-entry/memory
overhead from storing a namespace root was eliminated, and the remaining
`public_instanceof` regression was removed by keeping the const-class miss path
out of the hot handler. The remaining performance caveat is that these are
container microbenchmarks; representative application benchmarks are still
future evidence rather than a Gate 5 blocker.
