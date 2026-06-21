# Performance

No benchmarks were run in this iteration. All performance statements below are
design expectations or risks, not measured results.

## Data Size

Potential added data:

- one or more `ce_flags` bits on `zend_class_entry`;
- declaration namespace pointer on `zend_class_entry`;
- optional effective root pointer on `zend_class_entry`;
- possible caller namespace pointer on `zend_op_array` if the PR #20421 model is
  reused.

Current local structures:

- `zend_class_entry` has `ce_flags` and `ce_flags2`, but no namespace-visibility
  string field.
- `zend_op_array` has `run_time_cache`, `doc_comment`, `cache_size`, `filename`,
  and function/class scope fields, but no namespace field in this commit.

Size impact:

- Additional pointer fields on `zend_class_entry` affect every class-like
  declaration, including unrestricted classes, unless stored conditionally.
- Additional pointer field on `zend_op_array` affects functions, methods,
  closures, and scripts.
- OPcache shared memory size increases for persisted metadata and interned
  strings.

Not measured:

- exact `sizeof(zend_class_entry)` delta;
- exact `sizeof(zend_op_array)` delta;
- shared-memory increase across real applications.

## Runtime Cost

Potential hot paths:

- class fetch;
- `new`;
- static method/property/constant access;
- callable resolution;
- type resolution;
- inheritance linking;
- reflection construction;
- JIT known-class helpers.

The required fast path is:

```c
if (!(ce->ce_flags & ZEND_ACC_NAMESPACE_RESTRICTED)) {
    return SUCCESS;
}
```

For unrestricted classes, this should be a single predictable branch if placed
after CE resolution. This is an expectation, not a measurement.

For restricted classes, cost includes:

- fetching caller namespace;
- comparing namespace strings;
- segment-aware prefix check for `protected(namespace)`;
- possible diagnostic construction on failure.

## Runtime Cache

Runtime cache entries cannot simply skip checks after a CE is cached. The fast
path can still make unrestricted CEs cheap, but restricted CE cache hits need a
visibility check unless the cache is keyed by caller namespace.

Not measured:

- class fetch hit overhead;
- cache-miss overhead with autoload;
- callable resolution overhead;
- JIT helper overhead.

## OPcache and Preload

OPcache persistence must store metadata once and avoid per-request duplication
where possible. Preloaded class entries must retain metadata and not require
request-local reconstruction of namespace strings.

Not measured:

- persistent script size delta;
- interned string table delta;
- preload memory delta;
- invalidation cost.

## Benchmark Plan

After an implementation exists, benchmark:

- unrestricted `new` in tight loops;
- restricted allowed `new`;
- restricted denied `new`;
- static method calls on unrestricted and restricted classes;
- dynamic class-string construction;
- callable resolution;
- autoload miss and hit;
- OPcache enabled/disabled;
- JIT enabled/disabled.

Use representative applications only after microbenchmarks prove no obvious
hot-path regression.

