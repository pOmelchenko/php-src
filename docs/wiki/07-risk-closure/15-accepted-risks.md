# Accepted Risks

## Namespace Is Not Ownership

Any PHP code can declare the same namespace as a library. Namespace visibility
therefore cannot protect against malicious or intentionally adversarial code.

This is accepted because RFC v1 is an architectural feature for cooperating
codebases. It is similar to other encapsulation mechanisms that express intent
and prevent accidental use, but it is not a sandbox.

## Autoload Side Effects

If a class is not loaded, PHP may run autoload before the engine has access to
the target CE metadata. A forbidden access can therefore cause autoload side
effects before failing.

This is accepted for v1. Preventing it requires a manifest or module/package
metadata available before autoload.

## Name Visibility, Not Object Membrane

Objects of restricted classes can escape through public APIs. External code may
use those objects through public members and interfaces. This is accepted
because the feature restricts class-like names, not object identity.

## Reflection and String Visibility

Reflection, `get_class()`, serialized payloads, stack traces, debug output, and
`::class` strings may reveal restricted names. This is accepted because RFC v1
does not hide symbols; it restricts semantic use of class-like symbols.

## Public API Type Exposure

Public declarations may expose restricted type names if the declaration is in an
allowed namespace. This is accepted for v1 as a deferred consistent
accessibility problem, not as a hidden guarantee.

