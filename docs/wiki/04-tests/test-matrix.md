# Test Matrix

This matrix targets revised RFC v1:

```php
private(namespace) class-like declarations
protected(namespace) class-like declarations
```

Explicit root syntax remains Future Scope and must not be treated as a v1
acceptance test.

## Syntax

| ID | Risk IDs | Category | Case | Expected |
| --- | --- | --- | --- | --- |
| T-SYN-001 | RISK-002 | Accepted | `private(namespace) class A {}` | Parses |
| T-SYN-002 | RISK-003 | Accepted | `private(namespace) interface I {}` | Parses |
| T-SYN-003 | RISK-003 | Accepted | `private(namespace) trait T {}` | Parses |
| T-SYN-004 | RISK-003 | Accepted | `private(namespace) enum E { case A; }` | Parses |
| T-SYN-005 | RISK-001 | Accepted | `protected(namespace) class A {}` | Parses |
| T-SYN-006 | RISK-002 | Rejected | `private class A {}` | Not accepted by this RFC |
| T-SYN-007 | RISK-017 | Rejected | `internal class A {}` | Not accepted by this RFC |
| T-SYN-008 | RISK-006 | Rejected | `private(namespace: \A) class B {}` | Unsupported syntax |
| T-SYN-009 | RISK-003 | Rejected | `new private(namespace) class {}` | Unsupported on anonymous class |
| T-SYN-010 | RISK-002 | Rejected | Duplicate namespace visibility | Stable error |

## Exact Namespace

| ID | Risk IDs | Declaration | Caller | Expected |
| --- | --- | --- | --- | --- |
| T-EXACT-001 | RISK-004 | `Acme\Billing\private(namespace) A` | `Acme\Billing` same file | Allowed |
| T-EXACT-002 | RISK-004 | same | `Acme\Billing` other file | Allowed |
| T-EXACT-003 | RISK-006 | same | `Acme\Billing\App` | Denied |
| T-EXACT-004 | RISK-006 | same | `Acme` | Denied |
| T-EXACT-005 | RISK-006 | same | `Acme\Other` | Denied |
| T-EXACT-006 | RISK-006 | same | `Acme\BillingExtra` | Denied |
| T-EXACT-007 | RISK-013 | global declaration | global caller | Allowed |
| T-EXACT-008 | RISK-013 | global declaration | named caller | Denied |
| T-EXACT-009 | RISK-013 | named declaration | global caller | Denied |
| T-EXACT-010 | RISK-004 | `Foo\Bar` declaration | `foo\bar` caller | Allowed after normalization |
| T-EXACT-011 | RISK-004 | `Acme\Billing` declaration | alias-importing caller in `App` | Denied |
| T-EXACT-012 | RISK-004 | bracketed namespace | same normalized namespace | Allowed |
| T-EXACT-013 | RISK-004 | multiple blocks same namespace | same normalized namespace | Allowed |

## Protected Namespace Subtree

| ID | Risk IDs | Declaration | Caller | Expected |
| --- | --- | --- | --- | --- |
| T-PROT-001 | RISK-006 | `Acme\Billing\protected(namespace) A` | `Acme\Billing` | Allowed |
| T-PROT-002 | RISK-006 | same | `Acme\Billing\Domain` | Allowed |
| T-PROT-003 | RISK-006 | same | `Acme` | Denied |
| T-PROT-004 | RISK-006 | same | `Acme\Other` | Denied |
| T-PROT-005 | RISK-006 | same | `Acme\BillingExtra` | Denied |

## Lexical Caller

| ID | Risk IDs | Case | Expected |
| --- | --- | --- | --- |
| T-LEX-001 | RISK-004 | Named function in allowed namespace called from denied namespace | Allowed |
| T-LEX-002 | RISK-004 | Named function in denied namespace called from allowed namespace | Denied |
| T-LEX-003 | RISK-004 | Method declared in allowed namespace called from denied namespace | Allowed |
| T-LEX-004 | RISK-004 | Closure declared in allowed namespace called from denied namespace | Allowed |
| T-LEX-005 | RISK-004 | Arrow function declared in denied namespace called from allowed namespace | Denied |
| T-LEX-006 | RISK-004 | `Closure::bind()` changes `$this`/scope | Does not change caller namespace |
| T-LEX-007 | RISK-004 | `eval()` without namespace inside allowed namespace | Global caller |
| T-LEX-008 | RISK-004 | `eval()` with namespace declaration | Declared eval namespace |
| T-LEX-009 | RISK-012 | Trait body declared in allowed namespace used by denied class | Allowed for body operation |
| T-LEX-010 | RISK-012 | Trait body declared in denied namespace used by allowed class | Denied for body operation |
| T-LEX-011 | RISK-012 | Restricted trait use from denied class namespace | Denied |
| T-LEX-012 | RISK-004 | Inherited method containing restricted access | Uses inherited method body namespace |

## Semantic Operations

| ID | Risk IDs | Operation | Expected |
| --- | --- | --- | --- |
| T-OP-001 | RISK-004 | `new C()` | Checked |
| T-OP-002 | RISK-004 | `new $class` | Checked |
| T-OP-003 | RISK-004 | `C::method()` | Checked |
| T-OP-004 | RISK-004 | `C::$property` | Checked |
| T-OP-005 | RISK-004 | `C::CONST` | Checked |
| T-OP-006 | RISK-014 | `C::class` | Produces string, no check |
| T-OP-007 | RISK-004 | `extends C` | Checked |
| T-OP-008 | RISK-004 | `implements I` | Checked |
| T-OP-009 | RISK-004 | `interface A extends I` | Checked |
| T-OP-010 | RISK-012 | `use T` in class | Checked from consuming namespace |
| T-OP-011 | RISK-014 | `instanceof C` | Checked when CE resolved |
| T-OP-012 | RISK-014 | `catch (C $e)` | Checked when CE resolved |
| T-OP-013 | RISK-011 | Parameter type | Checked at type CE resolution |
| T-OP-014 | RISK-011 | Return type | Checked at type CE resolution |
| T-OP-015 | RISK-011 | Property type | Checked at type CE resolution |
| T-OP-016 | RISK-011 | Typed class constant | Checked at type CE resolution |
| T-OP-017 | RISK-011 | Promoted property | Checked at type CE resolution |
| T-OP-018 | RISK-011 | Union/intersection/DNF | Each class-like component checked |
| T-OP-019 | RISK-004 | Attribute class | Checked on attribute instantiation/validation |
| T-OP-020 | RISK-014 | Class name inside attribute argument via `::class` | String, later use checked |
| T-OP-021 | RISK-004 | First-class callable | Checked |
| T-OP-022 | RISK-004 | String callable | Checked at resolution/invocation |
| T-OP-023 | RISK-004 | Array callable | Class-string target checked |
| T-OP-024 | RISK-004 | `Closure::fromCallable()` | Checked |
| T-OP-025 | RISK-004 | `call_user_func()` | Checked |
| T-OP-026 | RISK-004 | `is_callable()` | No capability; inaccessible class-string callable must not invoke |
| T-OP-027 | RISK-014 | `class_exists()` family | May reveal existence, no capability |
| T-OP-028 | RISK-014 | `is_a()` / `is_subclass_of()` | Checked when class-string target is semantic use |
| T-OP-029 | RISK-014 | `method_exists()` / `property_exists()` | Metadata probe, no capability |
| T-OP-030 | RISK-015 | `class_alias()` | Alias preserves CE visibility |
| T-OP-031 | RISK-010 | `ReflectionClass` | Metadata allowed |
| T-OP-032 | RISK-010 | Reflection instantiation | Checked |
| T-OP-033 | RISK-004 | Serialization | Existing object allowed |
| T-OP-034 | RISK-004 | Unserialization | Checked before restricted object allocation |
| T-OP-035 | RISK-004 | Cloning existing object | Allowed by class-level visibility |
| T-OP-036 | RISK-004 | `__set_state` static call | Checked as static access |
| T-OP-037 | RISK-008 | Direct `require` | Load does not grant access |
| T-OP-038 | RISK-008 | Preload | No semantic widening |

## Cache and OPcache

| ID | Risk IDs | Case | Expected |
| --- | --- | --- | --- |
| T-CACHE-001 | RISK-008 | Allowed namespace loads first, denied uses cached CE | Denied |
| T-CACHE-002 | RISK-008 | Denied namespace fails first, allowed later uses CE | Allowed |
| T-CACHE-003 | RISK-008 | Alias created before denied access | Denied semantic use |
| T-CACHE-004 | RISK-008 | OPcache already contains class | Same as no OPcache |
| T-CACHE-005 | RISK-008 | Preloaded restricted class | Same as non-preload |
| T-CACHE-006 | RISK-008 | Two separate op_arrays | Caller namespace checked independently |
| T-CACHE-007 | RISK-008 | ZTS build where available | Same semantics |

## Error Assertions

Tests should assert stable error type and message text, not absolute paths.
Runtime violations throw `Error`. Compile/link failures may use existing PHP
fatal paths.
