# Terminology

## Class-like Declaration

A named top-level `class`, `interface`, `trait`, or `enum` declaration. The
working model excludes anonymous classes from namespace visibility modifiers.

## Declaration Namespace

The lexical namespace in effect at the point where the class-like declaration
is compiled. For files without a namespace declaration, the declaration
namespace is the global namespace.

## Caller Namespace

The lexical namespace of the operation that uses the restricted class-like
declaration. The caller namespace must be recoverable from compiled code or the
current engine operation. It is not `debug_backtrace()` and not the namespace of
the outermost user script that eventually caused execution.

## Lexical Namespace

The namespace written in PHP source for the operation or declaration after
normalization. The proposed checks should be based on lexical source context so
that behavior does not change based on autoload order or current object state.

## Descendant Namespace

Namespace `B` is a descendant of namespace `A` when `B` equals `A` followed by
one or more complete namespace segments. For example, `Acme\Billing\Application`
is a descendant of `Acme\Billing`; `Acme\BillingExtra` is not.

## Explicit Root

The future syntax `protected(namespace: \Acme\Billing)` names an allowed root
that must be the declaration namespace itself or one of its ancestors by full
namespace segments. Multiple roots and unrelated roots are out of first scope.

## Name Model

The proposed boundary model where visibility restricts resolution and use of a
class-like name. Once external code obtains an object through a public API, it
may use that object through public methods or public interfaces without naming
the restricted concrete class.

## Runtime Membrane

An alternative model where every method call or property access checks the
actual runtime class of the object against the caller namespace. This wiki
rejects it for the first prototype because it is broader, slower, less
compatible, and less aligned with the motivating factory/interface use case.

## Visibility Metadata

Flags and namespace strings stored on engine structures, most likely
`zend_class_entry`, that describe whether a class-like declaration is
namespace-restricted and what namespace or root governs access.

## Public API Exposure

The question whether a public class, method, property, class constant, or type
signature may mention a restricted class-like type. This is the "consistent
accessibility" problem known from several other languages. PHP's autoload and
lazy type resolution make this difficult to enforce globally at compile time.

