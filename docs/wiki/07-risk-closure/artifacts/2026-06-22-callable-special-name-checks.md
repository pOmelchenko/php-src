# 2026-06-22 Callable Special-Name Checks

This artifact records the follow-up optimization after the callable namespace
visibility cache work. It keeps the measured deltas in the wiki instead of
depending on transient profiler output files.

## Scope

| Field | Value |
| --- | --- |
| Base commit | `030dabd6d553a3f2fadf36b1d36e41caf17b89ed` |
| Candidate commit | `4de989f37977ef17c3ddcd722d9ca8a939721461` |
| Candidate summary | `Optimize callable class special-name checks` |
| Source file | `Zend/zend_API.c` |
| Function | `zend_is_callable_check_class()` |
| Benchmark harness | `benchmark/ns_visibility_gate5.php` |
| Container | `php-src-dev:bookworm` from `docker/dev/compose.yml` |
| Build | `CFLAGS="-O2 -g" ./configure --disable-all --enable-cli --enable-opcache --without-pear` |
| Runtime | Linux container, x86_64, release NTS CLI |

The retained change removes the per-call allocation and full class-name
lowercase copy used only to recognize callable class special names. The old
path lowercased the whole `name` string, then compared against `self`,
`parent`, and `static`. The new path performs the same exact case-insensitive
checks with `zend_string_equals_ci()` and leaves ordinary class lookup on the
original `name`.

## Correctness Checks

Targeted PHPT run:

```text
Zend/tests/access_modifiers/ns_visibility_callable_cache.phpt
Zend/tests/access_modifiers/ns_visibility_gate3_callables_reflection_serialization.phpt
Zend/tests/access_modifiers/access_modifiers_009.phpt
Zend/tests/lsb/lsb_011.phpt
Zend/tests/lsb/lsb_012.phpt
Zend/tests/lsb/lsb_013.phpt
Zend/tests/lsb/lsb_021.phpt
Zend/tests/lsb/lsb_022.phpt
```

Result: 8/8 passed.

One-off mixed-case smoke:

| Callable | Result |
| --- | --- |
| `SeLf::ok` | true |
| `PARENT::ok` | true |
| `Static::ok` | true |
| `Foo\SeLf::ok` | false |
| `self\Foo::ok` | false |
| `self ::ok` | false |

## Wall-Clock Sanity Runs

Single-worker runs used 10,000,000 iterations, five measured runs, and one
warmup. These are retained as sanity checks only; Docker wall-clock showed
visible outliers in the no-OPcache public row and the OPcache/private row.
Callgrind below is the main evidence for the optimization.

| Mode | Case | Base median ns/iter | Candidate median ns/iter | Delta | Candidate RSD |
| --- | --- | ---: | ---: | ---: | ---: |
| no OPcache | `public_callable_validation` | 104.310 | 94.533 | -9.37% | 9.60% |
| no OPcache | `private_allowed_callable_validation` | 108.989 | 103.513 | -5.02% | 3.11% |
| OPcache, no JIT | `public_callable_validation` | 58.297 | 50.297 | -13.72% | 0.76% |
| OPcache, no JIT | `private_allowed_callable_validation` | 61.100 | 54.195 | -11.30% | 8.24% |

## Callgrind Totals

Callgrind workers used 100,000 iterations and the same benchmark cases. The
candidate reduces total retired instructions in all four targeted profiles.

| Mode | Case | Base Ir | Candidate Ir | Delta |
| --- | --- | ---: | ---: | ---: |
| no OPcache | `public_callable_validation` | 141,127,886 | 130,128,020 | -7.79% |
| no OPcache | `private_allowed_callable_validation` | 151,188,675 | 137,188,776 | -9.26% |
| OPcache, no JIT | `public_callable_validation` | 93,533,876 | 82,534,108 | -11.76% |
| OPcache, no JIT | `private_allowed_callable_validation` | 99,389,199 | 85,389,378 | -14.09% |

The hottest local function cost in the OPcache/no-JIT profiles dropped by about
half:

| Case | Base `zend_is_callable_check_class()` Ir | Candidate Ir | Delta |
| --- | ---: | ---: | ---: |
| `public_callable_validation` | 20,900,000 | 9,900,000 | -52.63% |
| `private_allowed_callable_validation` | 25,200,094 | 11,200,092 | -55.56% |

The base OPcache/no-JIT profiles showed
`zend_str_tolower_copy(ZSTR_VAL(lcname), ZSTR_VAL(name), name_len)` inside
`zend_is_callable_check_class()` at 100,000 calls. The candidate profiles no
longer call `zend_str_tolower_copy()` from that function.

## Interpretation

This is behavior-preserving for language semantics because the old and new
conditions both mean "the whole class-name string equals `self`, `parent`, or
`static` case-insensitively." Namespaced strings, whitespace variants, and
ordinary class names still fall through to `zend_lookup_class(name)`.

The next visible cost after this change is ordinary class lookup lowering for
runtime class strings. That path belongs to the broader class-entry cache
mechanism; this sweep did not change it because automatically adding CE-cache
slots to arbitrary runtime strings would have memory and lifetime implications.
