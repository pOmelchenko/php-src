# Performance Evidence

Performance evidence is **NOT MEASURED** for the selected private/protected v1
model.
The existing Phase C spike is not complete enough to benchmark as RFC evidence.

## Required Benchmark Record

| Field | Value |
| --- | --- |
| Build configuration | NOT MEASURED |
| Compiler | NOT MEASURED |
| Debug/release | NOT MEASURED |
| ZTS/NTS | NOT MEASURED |
| JIT on/off | NOT MEASURED |
| OPcache on/off | NOT MEASURED |
| CPU and OS | NOT MEASURED |
| Commit hash | `1c1d3a699c624030ca0582daedfebcba723c8ddc` for this documentation phase |
| Benchmark command | NOT MEASURED |
| Runs | NOT MEASURED |
| Median | NOT MEASURED |
| Variance | NOT MEASURED |

## Required Measurements

| Measurement | Status |
| --- | --- |
| Repeated instantiation public class | NOT MEASURED |
| Repeated static access public class | NOT MEASURED |
| Repeated `instanceof` public class | NOT MEASURED |
| Allowed access restricted class | NOT MEASURED |
| Class lookup after cache warmup | NOT MEASURED |
| Autoloaded access | NOT MEASURED |
| OPcache memory impact | NOT MEASURED |
| `sizeof(zend_class_entry)` | NOT MEASURED |
| `sizeof(zend_op_array)` if changed | NOT MEASURED |

## Design Requirement

The intended public fast path is a predictable flag check after CE resolution:

```c
if (!(ce->ce_flags2 & ZEND_ACC2_NAMESPACE_RESTRICTED)) {
    return true;
}
```

This is not a benchmark result. Gate 5 remains failed until measurements are
recorded with reproducible commands and multiple runs.
