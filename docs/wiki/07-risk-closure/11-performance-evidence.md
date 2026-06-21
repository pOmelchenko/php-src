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

The 2026-06-22 follow-up sweep removed the remaining per-check allocation from
restricted namespace-visibility decisions. The public paired rows remain within
the retained Gate 5 threshold after longer reruns for sub-nanosecond JIT rows.
The restricted callable-validation row improves by 27-38% by paired median,
with Callgrind showing a 23.14% instruction-count reduction for the OPcache/no
JIT profile.

A second measured 2026-06-22 follow-up adds an exact-case namespace-prefix fast
path before the case-insensitive fallback. That further improves restricted
callable validation by 15-21% over the allocation-removal build, and reduces the
OPcache/no-JIT Callgrind profile by another 16.00%. Public reruns remain within
Gate 5; the only threshold blip, tracing-JIT `public_instanceof`, flips to
-1.27% in a longer 200M-iteration rerun with high RSD.

Raw artifacts were kept outside the repository under
`/tmp/nsvis-gate5-results/` and `/tmp/nsvis-perf-pass-20260622/`.

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
| `nsvis-perf-pass-20260622/current-baseline.json` | Follow-up full current matrix at `96b5562` before the allocation removal |
| `nsvis-perf-pass-20260622/after-allocation-removal.json` | Follow-up full matrix after the allocation removal |
| `nsvis-perf-pass-20260622/paired-allocation-removal.json` | Seven-pair before/after public rows and targeted restricted rows |
| `nsvis-perf-pass-20260622/paired-long-allocation-removal.json` | Longer paired reruns for noisy sub-10 ns public rows |
| `nsvis-perf-pass-20260622/paired-static-method-1b/*` | Nine-pair 1B-iteration rerun for tracing-JIT `public_static_method` |
| `nsvis-perf-pass-20260622/profiles/callgrind-*-opcache-nojit-*` | Follow-up Callgrind profiles for restricted rows |
| `nsvis-perf-pass-20260622/profiles/cachegrind-*-private_allowed_callable_validation.out` | Follow-up Cachegrind pair for the main restricted callable target |
| `nsvis-perf-pass-20260622/exactcase-full.json` | Full Gate 5 matrix after the exact-case prefix fast path |
| `nsvis-perf-pass-20260622/paired-exactcase-final.json` | Seven-pair allocation-removal/exact-case public rows and targeted restricted rows |
| `nsvis-perf-pass-20260622/paired-exactcase-final-long/*` | Longer exact-case paired reruns for noisy public rows |
| `nsvis-perf-pass-20260622/paired-exactcase-instanceof-200m/*` | Nine-pair 200M-iteration exact-case rerun for tracing-JIT `public_instanceof` |
| `nsvis-perf-pass-20260622/exactcase-profiles/callgrind-*-private_allowed_callable_validation.out` | Exact-case Callgrind pair for the main restricted callable target |

Rejected experiments were also saved (`current-cachehit-global*`,
`current-globalflag*`). They are not retained: the executor-global
restricted-class flag worsened `instanceof`. The 2026-06-22 sweep also rejected
an optimizer/JIT namespace-range shortcut: using transformed optimizer oplines
to prove restricted-class visibility caused
`ext/opcache/tests/ns_visibility_opcache_namespace_ranges.phpt` to allow a
consumer access that must throw, so the shortcut was reverted.

## Benchmark Record

| Field | Value |
| --- | --- |
| Date | 2026-06-21 |
| Baseline commit | `0fff3ccce2f5f9e0695502a509fa8e8edf8f77d4` |
| Current commit | `6391d08d0cbc4f25d38e9ac60fe7873b0a47e785` plus local Gate 5 optimization, class-entry storage removal, `instanceof` cold miss helper, and docs edits |
| Follow-up date | 2026-06-22 |
| Follow-up current commit | `96b5562a1b9b05548bd50848640af4ed8e5f216f` plus local restricted-check allocation removal |
| Exact-case follow-up base | `585d82a6f662b9cd62ccc3d78db8472750037e7b` |
| Exact-case follow-up candidate | `585d82a6f662b9cd62ccc3d78db8472750037e7b` plus local exact-case namespace-prefix fast path |
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

The 2026-06-22 allocation-removal follow-up used the same Docker image and
release NTS CLI build. It built saved before/after binaries from `96b5562` with
and without the local allocation-removal patch, then ran alternating paired
workers from those binaries. The exact-case follow-up reused the saved
allocation-removal binary as `php-after` and compared it with `php-exactcase`.
The follow-up artifacts live in `/tmp/nsvis-perf-pass-20260622/`.

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

## 2026-06-22 Restricted Allocation Removal

The follow-up sweep targeted the remaining allocation in restricted visibility
checks. Before this change, the hot check lowercased the caller namespace and
derived/lowercased the declaration namespace from `ce->name` on each restricted
visibility decision. The retained change keeps
`zend_get_class_namespace_visibility_root()` allocation-based for Reflection and
diagnostics, but the allow/deny predicate now compares the caller namespace
directly against the namespace prefix of `ce->name` with a case-insensitive
segment-boundary check.

The full matrix was run before and after the change. Because some JIT rows are
sub-nanosecond and noisy, acceptance used alternating paired workers from saved
before/after binaries.

### Public Gate Follow-Up

All standard-scale public paired rows stayed within the `<= 3%` public hot-path
threshold. Rows that crossed the threshold or had high RSD in the short paired
matrix were rerun with higher iteration counts:

| Mode | Measurement | Iterations | Before | After | Delta | RSD before/after | Result |
| --- | --- | ---: | ---: | ---: | ---: | ---: | --- |
| OPcache, no JIT | `public_class_constant` | 50000000 | 1.770 | 1.753 | -0.98% | 1.69% / 0.89% | Pass |
| OPcache, tracing JIT | `public_static_method` | 1000000000 | 0.306 | 0.310 | +1.41% | 1.65% / 1.79% | Pass |
| OPcache, tracing JIT | `public_static_property` | 300000000 | 0.597 | 0.594 | -0.52% | 0.51% / 0.86% | Pass |
| OPcache, tracing JIT | `public_class_constant` | 300000000 | 0.306 | 0.305 | -0.26% | 3.03% / 2.37% | Pass |
| OPcache, tracing JIT | `public_instanceof` | 50000000 | 2.238 | 2.264 | +1.15% | 5.19% / 4.08% | Pass/noisy |

The remaining public rows in `paired-allocation-removal.json` were within the
threshold in the short seven-pair matrix. The largest positive standard-scale
public deltas were `public_instanceof` without OPcache at +2.37% and
`public_autoload_hit` under tracing JIT at +1.95%.

### Restricted Paired Rows

Targeted restricted rows improved by paired median in all modes:

| Mode | Measurement | Before | After | Delta | RSD before/after |
| --- | --- | ---: | ---: | ---: | ---: |
| no OPcache | `private_allowed_callable_validation` | 83.892 | 60.904 | -27.40% | 1.72% / 1.05% |
| no OPcache | `private_denied_new` | 473.779 | 443.521 | -6.39% | 1.00% / 0.76% |
| OPcache, no JIT | `private_allowed_callable_validation` | 54.155 | 34.889 | -35.58% | 4.77% / 0.82% |
| OPcache, no JIT | `private_denied_new` | 457.608 | 430.146 | -6.00% | 1.22% / 1.85% |
| OPcache, function JIT | `private_allowed_callable_validation` | 54.119 | 34.078 | -37.03% | 1.16% / 4.21% |
| OPcache, function JIT | `private_denied_new` | 457.867 | 424.321 | -7.33% | 1.44% / 1.08% |
| OPcache, tracing JIT | `private_allowed_callable_validation` | 51.952 | 32.294 | -37.84% | 0.57% / 2.58% |
| OPcache, tracing JIT | `private_denied_new` | 458.433 | 426.771 | -6.91% | 0.98% / 1.31% |

### Profiler Check

Callgrind used OPcache/no-JIT workers with 100000 iterations. The small class
operation rows save only the removed bookkeeping around the check; the main
winner is callable validation, which repeats the restricted visibility check on
every `is_callable()` probe.

| Case | Before Ir | After Ir | Delta |
| --- | ---: | ---: | ---: |
| `private_allowed_new` | 60,068,640 | 60,056,616 | -0.02% |
| `private_allowed_static_method` | 21,867,625 | 21,855,998 | -0.05% |
| `private_allowed_static_property` | 26,468,602 | 26,456,632 | -0.05% |
| `private_allowed_class_constant` | 21,867,658 | 21,856,000 | -0.05% |
| `private_allowed_instanceof` | 27,468,703 | 27,456,660 | -0.04% |
| `private_allowed_callable_validation` | 165,968,531 | 127,556,879 | -23.14% |
| `protected_allowed_child_instanceof` | 27,469,087 | 27,457,078 | -0.04% |
| `private_denied_new` | 712,167,603 | 653,457,523 | -8.24% |

Before the change, `zend_string_tolower_ex` accounted for 32,151,591 Ir
(19.37%) in the OPcache/no-JIT `private_allowed_callable_validation` profile.
It does not appear in the after profile at the same threshold.

Cachegrind for the same callable-validation worker shows total Ir moving from
165,983,266 to 127,571,622 (-23.14%). Data reads plus writes move from
70,991,225 to 55,386,484 (-21.98%). Cachegrind could not auto-detect the host
cache geometry in the container, so the cache-miss counters are retained as
artifacts but not used for the acceptance claim.

### Exact-Case Prefix Fast Path

After allocation removal, the exact-case allowed path still called
`zend_binary_strncasecmp()` for every restricted namespace comparison. The
retained follow-up checks the same namespace prefix with `memcmp()` first and
keeps the case-insensitive fallback for mixed-case callers. The segment-boundary
check remains outside the helper, so `Foo` still does not match `Foobar`.

Targeted restricted callable-validation rows improved again by paired median:

| Mode | Measurement | Before | After | Delta | RSD before/after |
| --- | --- | ---: | ---: | ---: | ---: |
| no OPcache | `private_allowed_callable_validation` | 62.649 | 53.496 | -14.61% | 3.62% / 0.66% |
| OPcache, no JIT | `private_allowed_callable_validation` | 35.877 | 28.766 | -19.82% | 0.63% / 1.31% |
| OPcache, function JIT | `private_allowed_callable_validation` | 35.004 | 27.970 | -20.09% | 0.90% / 1.64% |
| OPcache, tracing JIT | `private_allowed_callable_validation` | 32.669 | 25.819 | -20.97% | 1.19% / 2.22% |

The denied `new` rows were also measured and are not claimed as a win: they
range from -0.36% to +1.30% by paired median across the four modes.

Public rows that crossed the short-matrix threshold or had high RSD were rerun
with higher iteration counts:

| Mode | Measurement | Iterations | Before | After | Delta | RSD before/after | Result |
| --- | --- | ---: | ---: | ---: | ---: | ---: | --- |
| OPcache, function JIT | `public_new` | 10000000 | 25.668 | 26.106 | +1.71% | 0.43% / 1.36% | Pass |
| OPcache, tracing JIT | `public_static_property` | 300000000 | 0.597 | 0.602 | +0.78% | 0.88% / 0.53% | Pass |
| OPcache, tracing JIT | `public_dynamic_lookup_warm` | 50000000 | 3.761 | 3.819 | +1.55% | 1.21% / 2.95% | Pass |
| OPcache, tracing JIT | `public_instanceof` | 50000000 | 2.268 | 2.339 | +3.14% | 4.08% / 3.87% | Noisy; rerun below |
| OPcache, tracing JIT | `public_instanceof` | 200000000 | 2.372 | 2.342 | -1.27% | 4.71% / 6.74% | Pass/noisy |

Callgrind used OPcache/no-JIT `private_allowed_callable_validation` workers with
100000 iterations:

| Case | Before Ir | After Ir | Delta |
| --- | ---: | ---: | ---: |
| `private_allowed_callable_validation` | 127,555,145 | 107,152,531 | -16.00% |

The allocation-removal profile still contained calls to
`zend_binary_strncasecmp()` for the exact-case workload. The exact-case profile
does not call that function on the retained hot path.

### Rejected Follow-Up Candidates

The namespace-range lookup itself was not changed: profiling did not identify
range lookup as a material cost in the retained benchmarks, and the current
reverse scan is preserved.

An optimizer/JIT shortcut that used
`zend_get_op_array_lexical_namespace_at(op_array, opline)` for op arrays with
top-level namespace ranges was attempted and rejected. The JIT namespace-range
test still passed, but the non-JIT OPcache namespace-range test failed by
allowing access from `Gate4\OpcacheRanges\OtherCase` to a
`private(namespace)` class in `Gate4\OpcacheRanges\MiXeDCase`. The transformed
optimizer opline was not a safe key for this decision, so the conservative
`last_namespace_range > 0` fallback remains.

The public known-`INSTANCEOF` JIT cleanup was not pursued in this sweep. The
paired public `instanceof` rows pass after the earlier cold-miss helper, and no
new measured bottleneck justified changing `zend_may_throw()` semantics.

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
| Allowed access restricted class | Measured; allocation removal and exact-case fast path improve callable-validation paired rows |
| Class lookup after cache warmup | Measured; paired row passes |
| Autoloaded access | Measured; long rerun passes |
| Denied restricted access | Measured separately with low-iteration error-path loop; follow-up paired rows improve |
| OPcache memory impact | Measured |
| `sizeof(zend_class_entry)` | Measured |
| `sizeof(zend_op_array)` | Measured |
| `sizeof(zend_op_array_namespace_range)` | Measured on current |

## Conclusion

Gate 5 is **passed** for the retained public hot-path benchmark gate. The major
public cache-hit regressions were found and fixed, the class-entry/memory
overhead from storing a namespace root was eliminated, and the remaining
`public_instanceof` regression was removed by keeping the const-class miss path
out of the hot handler. The follow-up sweep also removes allocation from
restricted visibility decisions, then removes the common exact-case
case-insensitive compare from allowed restricted checks, without moving public
hot paths outside the Gate 5 threshold. The remaining performance caveat is that
these are container microbenchmarks; representative application benchmarks are
still future evidence rather than a Gate 5 blocker.
