# Decision Log

Decisions marked `accepted-for-prototype` are implementation-direction choices
for a possible local spike, not accepted PHP language decisions.

Decisions DEC-020 and later are the Phase 2 RFC v1 decisions. They supersede
earlier prototype decisions where they conflict. DEC-020/022/035 were revised
after bounded-context feedback showed that exact-only visibility does not cover
layered namespace trees.

## DEC-001: Plain `private`/`protected` or Qualified Modifiers

Status: superseded-by-DEC-021-and-DEC-022

Context:
Top-level class-like visibility needs syntax that does not confuse member
visibility, inheritance-based `protected`, or future file/module visibility.

Options:

- plain `private class` / `protected class`;
- qualified `private(namespace)` / `protected(namespace)`;
- `internal`;
- built-in attribute.

Decision:
The first prototype used `private(namespace)` and `protected(namespace)`.
Current RFC v1 keeps only `private(namespace)`; `protected(namespace)` is not
used for descendant visibility.

Consequences:
The syntax is explicit, keeps `private(file)` available, and avoids adding
`internal` without a module boundary.

Evidence:
Current PHP has `private(set)` and `protected(set)` token precedents. PR #20421
uses `private(namespace)` for members/properties.

## DEC-002: Exact Private Semantics

Status: superseded-by-DEC-035

Context:
`private(namespace)` must define whether child namespaces have access.

Options:

- exact namespace only;
- namespace plus descendants;
- file-only.

Decision:
Use exact namespace equality.

Consequences:
`Acme\Billing\Application` cannot access a `private(namespace)` declaration in
`Acme\Billing`.

Evidence:
Matches PR #20421's exact lexical namespace direction for
`private(namespace)`.

## DEC-003: Descendant Protected Semantics

Status: accepted-for-prototype

Context:
`protected(namespace)` must define a namespace-tree rule.

Options:

- exact namespace only;
- declaration namespace plus descendants;
- common-prefix package model;
- inheritance/subclass model.

Decision:
The early prototype used declaration namespace plus descendant namespaces by
complete segments. Current RFC v1 defers descendant visibility.

Consequences:
`Acme\Billing\Application` is allowed; `Acme\BillingExtra` is denied.

Evidence:
This matches the motivating requirement and aligns with D/Rust ancestor-root
prior art when future explicit roots are considered.

## DEC-004: Lexical Caller

Status: accepted-for-prototype

Context:
The caller identity could come from source lexical namespace, runtime stack, or
current object.

Options:

- lexical namespace of the operation;
- `debug_backtrace()`/runtime caller;
- current object or current class;
- autoloader namespace.

Decision:
Use lexical namespace of the operation.

Consequences:
Closures, eval, traits, reflection, and internal callbacks need explicit
metadata/context. Autoload order cannot change access.

Evidence:
PR #20421 uses lexical namespace for member namespace visibility.

## DEC-005: Trait Semantics

Status: accepted-for-prototype

Context:
Operations written inside trait methods can be attributed to the trait
declaration namespace or the using class namespace.

Options:

- trait declaration namespace;
- using class namespace after composition.

Decision:
For the first prototype, use the using class namespace after composition for
operations inside trait methods. For `use RestrictedTrait`, check the using
class declaration namespace.

Consequences:
Trait access may vary by use site. Static analysis and tests must cover this.

Evidence:
This aligns with PR #20421's treatment of trait methods for namespace-private
members.

## DEC-006: Object Escape

Status: accepted-for-prototype

Context:
A restricted concrete class may implement a public interface and be returned by
a public factory.

Options:

- restrict name use only;
- check every method/property operation on the runtime object's actual class.

Decision:
Use the name model.

Consequences:
External code may use an obtained object through public API without naming the
restricted concrete class.

Evidence:
The old class-like namespace visibility RFC also allowed operations on already
obtained objects. The motivating factory/interface pattern requires this.

## DEC-007: `::class`

Status: superseded-by-DEC-028

Context:
`SomeClass::class` normally produces a string without autoload.

Options:

- always allow string production and enforce later;
- check only if target is already loaded;
- force autoload/check.

Decision:
Superseded by DEC-028. RFC v1 allows string production and enforces when the
string is later used as a class name.

Consequences:
Class strings for restricted classes may be visible.

Evidence:
Changing `::class` into an autoloading operation would be a significant PHP
behavior change.

## DEC-008: Aliases

Status: accepted-for-prototype

Context:
`class_alias()` can introduce another name for the same class entry.

Options:

- alias widens visibility;
- alias preserves class-entry visibility.

Decision:
Alias preserves visibility because metadata belongs to `zend_class_entry`.

Consequences:
A public-looking alias cannot bypass restrictions.

Evidence:
OPcache persistence code already comments that the same `zend_class_entry` may
be reused by `class_alias()`.

## DEC-009: Reflection

Status: superseded-by-DEC-031

Context:
Reflection already has privileged behavior for some member operations.

Options:

- reflection fully bypasses class-like visibility;
- reflection can read metadata but construction enforces visibility;
- reflection always enforces every operation.

Decision:
Superseded by DEC-031. Metadata is readable and Reflection construction is
checked in RFC v1.

Consequences:
`new ReflectionClass()` may inspect a restricted class, but
`newInstance()` should not be a construction bypass unless RFC voters choose
that explicitly.

Evidence:
Reflection member bypass is prior art but does not automatically settle
class-like name access.

## DEC-010: Public API Exposure

Status: superseded-by-DEC-033-and-DEC-034

Context:
Public signatures can mention restricted types.

Options:

- fully forbid inconsistent accessibility;
- allow and check use;
- defer to a later RFC.

Decision:
Superseded by DEC-033 and DEC-034. RFC v1 allows declaration-site use and
defers native consistent accessibility.

Consequences:
The first implementation should not claim full API consistency.

Evidence:
PHP autoloading and lazy type resolution make global compile-time enforcement
difficult.

## DEC-011: Global Namespace

Status: superseded-by-DEC-025

Context:
An empty namespace breaks the naive descendant-prefix rule.

Options:

- reject global `protected(namespace)`;
- treat it as exact global;
- allow it to mean public.

Decision:
Superseded by DEC-025. RFC v1 treats global namespace as the exact empty
namespace.

Consequences:
The RFC must explicitly define global behavior.

Evidence:
The requirement says global root must not accidentally turn restricted class
into public.

## DEC-012: Explicit Root

Status: superseded-by-DEC-036

Context:
Future syntax may name an ancestor root.

Options:

- include in first prototype;
- document as Future Scope;
- support arbitrary roots or friends.

Decision:
Superseded by DEC-036. Explicit root is deferred from RFC v1.

Consequences:
Base implementation stays smaller.

Evidence:
D `package(root)` and Rust `pub(in ancestor)` support the ancestor-root idea,
but PHP needs base semantics first.

## DEC-013: Error Type

Status: superseded-by-DEC-039

Context:
Violations may happen at compile, link, autoload, or runtime.

Options:

- always `Error`;
- compile/link fatal when operation is declaration linkage, `Error` for runtime;
- custom exception.

Decision:
Superseded by DEC-039. Check after CE resolution; runtime violations throw
`Error`; compile/link paths may use existing fatal errors.

Consequences:
Messages should include target class, caller namespace, and allowed scope, but
not absolute file paths.

Evidence:
PHP already uses fatal compile/link errors and runtime `Error` for many engine
violations.

## DEC-014: Class-like Coverage

Status: accepted-for-prototype

Context:
The modifier must define applicable declarations.

Options:

- only classes;
- classes/interfaces/traits/enums;
- include anonymous classes.

Decision:
Support named class, interface, trait, and enum declarations. Exclude anonymous
classes.

Consequences:
Parser and metadata need to cover all `ZEND_AST_CLASS` class-kind flags except
anonymous classes.

Evidence:
The user-level goal requires class-like declarations; anonymous classes do not
have stable top-level names.

## DEC-015: `internal`

Status: accepted-for-prototype

Context:
Several languages have `internal`, but they also have modules/assemblies.

Options:

- add `internal` as namespace visibility;
- reserve `internal` for future modules/packages;
- never use `internal`.

Decision:
Do not add `internal` in the first prototype; reserve it for a real module or
package boundary discussion.

Consequences:
The prototype does not conflate namespace and Composer package.

Evidence:
C#, Kotlin, and Swift all tie `internal`-style visibility to a real compilation
or package unit.

## DEC-016: Namespace Visibility Modifier Order

Status: implementation-gate

Context:
The parser spike must decide whether `private(namespace)` behaves like a normal
class modifier that can appear in any class-modifier order, or like a special
declaration prefix shared by class, interface, trait, and enum productions.

Options:

- accept only prefix order, e.g. `private(namespace) abstract class A {}`;
- accept both prefix and mixed order, e.g. also
  `abstract private(namespace) class A {}`;
- reject class modifiers after namespace visibility until a fuller grammar
  design exists.

Decision:
The current Phase B spike accepts only prefix order. This is a prototype
constraint, not a final language decision.

Consequences:
The grammar is conflict-free and works uniformly for named class, interface,
trait, and enum declarations. A later RFC-quality implementation must either
justify prefix-only ordering or extend duplicate/conflicting modifier handling
to support mixed order.

Evidence:
The Docker-generated parser reports zero conflicts with the prefix-only
grammar. Earlier broad optional modifier productions introduced reduce/reduce
conflicts.

## DEC-020: First RFC includes exact private and protected namespace subtree

Status: accepted-for-rfc-v1
Risk IDs: RISK-003, RISK-006
Context: The original research model included exact namespace, descendants, and explicit roots.
Options: Exact only; exact plus descendants; exact plus descendants plus explicit root.
Decision: The first RFC includes exact `private(namespace)` and subtree
`protected(namespace)`.
Normative rule: `private(namespace)` requires exact normalized namespace
equality. `protected(namespace)` allows exact equality or a descendant namespace
with a complete segment boundary.
Consequences: `App\Billing\Domain` can access `protected(namespace)`
declarations in `App\Billing`, but cannot access `private(namespace)`
declarations there. Parent, sibling, and prefix-similar namespaces are denied.
Evidence: Exact-only is too narrow for bounded-context layouts with layered
namespaces.
Tests: `T-EXACT-CHILD-DENIED`, `T-PROT-CHILD-ALLOWED`,
`T-PROT-SIBLING-DENIED`, `T-PROT-PREFIX-DENIED`.
Residual risk: `protected(namespace)` has terminology risk with inheritance.
Revisit condition: Internals rejects class-level `protected(namespace)` as a
namespace-subtree spelling.

## DEC-021: Qualified class modifier syntax

Status: accepted-for-rfc-v1
Risk IDs: RISK-001, RISK-002
Context: Plain `private class` conflicts with private classes/functions;
bounded contexts need a broader namespace-subtree spelling.
Options: `private class`; `private(namespace) class`; `protected(namespace)
class`; attributes; `internal`.
Decision: Use `private(namespace)` and `protected(namespace)` for RFC v1.
Normative rule: `private(namespace)` and `protected(namespace)` may modify named
class-like declarations supported by RFC v1.
Consequences: `private(file)` and `private(module)` remain available for future RFCs.
Evidence: Active member RFC uses `private(namespace)` for exact namespace member access.
Tests: `T-SYN-PRIVATE-NAMESPACE-ACCEPT`, `T-SYN-PROTECTED-NAMESPACE-ACCEPT`,
`T-SYN-PRIVATE-CLASS-NOT-V1`.
Residual risk: Parser/tooling must learn qualified modifier syntax and the
class-level protected meaning.
Revisit condition: Member RFC syntax changes before discussion.

## DEC-022: Use protected(namespace) for class-level descendants with caveat

Status: accepted-for-rfc-v1
Risk IDs: RISK-001, RISK-006
Context: `protected(namespace)` may be read as inheritance protected plus namespace access.
Options: Use for descendants; reserve for inheritance combination; reject in v1.
Decision: Use `protected(namespace)` for class-level namespace descendants in
RFC v1, but document the conflict explicitly.
Normative rule: `protected(namespace)` on a class-like declaration allows the
declaring namespace and descendant namespaces.
Consequences: The RFC must explain that class-level `protected(namespace)` is
not inheritance visibility and may need a separate vote question.
Evidence: Current member RFC lists `protected(namespace)` Future Scope as namespace plus inheritance, not descendants.
Tests: `T-SYN-PROTECTED-NAMESPACE-ACCEPT`, `T-PROT-CHILD-ALLOWED`.
Residual risk: Internals may object to overloading `protected`.
Revisit condition: If the terminology objection dominates, switch to a
non-`protected` subtree spelling before discussion.

## DEC-023: Class-name visibility versus object membrane

Status: accepted-for-rfc-v1
Risk IDs: RISK-004
Context: A restricted implementation object may escape through a public factory.
Options: Restrict class-name use; check each object operation; restrict construction only.
Decision: Use class-name symbol visibility, not an object membrane.
Normative rule: Existing objects are not denied public object operations solely because their concrete class is restricted.
Consequences: Public interface/factory patterns work; `instanceof RestrictedClass` remains denied outside the namespace.
Evidence: Old encapsulation and namespace-visibility drafts treated obtained objects as usable.
Tests: `T-OBJ-PUBLIC-METHOD`, `T-OBJ-CLONE`, `T-OBJ-GET-CLASS`, `T-OBJ-INSTANCEOF-DENIED`.
Residual risk: Restricted class names may appear in runtime metadata.
Revisit condition: A separate object membrane RFC.

## DEC-024: Lexical namespace

Status: accepted-for-rfc-v1
Risk IDs: RISK-004, RISK-008, RISK-012
Context: Caller identity could be runtime stack, object scope, autoloader, or lexical source.
Options: Lexical namespace; runtime caller; current object; autoloader namespace.
Decision: Use lexical namespace of the operation.
Normative rule: Access does not depend on call stack, `$this`, `debug_backtrace()`, autoload namespace, or load order.
Consequences: Engine must carry lexical namespace metadata into runtime/linking paths.
Evidence: Member RFC/PR #20421 uses lexical namespace; PHP source stores and resolves namespace context during compilation.
Tests: `T-LEX-FUNCTION`, `T-LEX-METHOD`, `T-LEX-CLOSURE`, `T-LEX-EVAL`, `T-CACHE-ORDER`.
Residual risk: Trait and internal operation paths require extra metadata.
Revisit condition: Implementation cannot supply lexical caller for a significant operation.

## DEC-025: Global namespace

Status: accepted-for-rfc-v1
Risk IDs: RISK-013
Context: Empty namespace root is ambiguous for descendants.
Options: Reject global; exact global; public by accident.
Decision: Global namespace is represented by empty string. It is valid for exact
private access; protected descendant access from the empty root is not granted
in v1.
Normative rule: Global callers can access global `private(namespace)` and
`protected(namespace)` declarations; named callers cannot access them merely by
being non-empty namespaces.
Consequences: No accidental "all namespaces are descendants of global" behavior.
Evidence: The runtime protected check requires a non-empty declaration namespace
before allowing descendant access.
Tests: `T-GLOBAL-GLOBAL`, `T-GLOBAL-NAMED`, `T-NAMED-GLOBAL`.
Residual risk: Explicit global-root syntax must define this separately.
Revisit condition: RFC C includes explicit roots from global.

## DEC-026: Trait composition

Status: accepted-for-rfc-v1
Risk IDs: RISK-012
Context: `class C { use T; }` names a trait during class composition.
Options: Check from trait namespace; check from consuming class namespace; no check.
Decision: Check trait `use` from the consuming class declaration namespace.
Normative rule: A class may use a restricted trait only if the consuming class declaration namespace can access the trait CE.
Consequences: External classes cannot compose restricted traits.
Evidence: Trait use is a class-linking operation written in the consuming class declaration.
Tests: `T-TRAIT-USE-ALLOWED`, `T-TRAIT-USE-DENIED`.
Residual risk: OPcache inheritance cache must not bypass this check.
Revisit condition: Class-linking implementation cannot pass caller namespace.

## DEC-027: Trait body operations

Status: accepted-for-rfc-v1
Risk IDs: RISK-012
Context: PHP rewrites trait method scope to the using class, but the source operation is written in the trait.
Options: Trait declaration namespace; using class namespace; mixed by operation.
Decision: Operations written inside trait bodies use the trait declaration namespace.
Normative rule: A trait body cannot gain namespace access merely by being used in an allowed class.
Consequences: Implementation needs original trait declaration namespace metadata because `zend_fixup_trait_method()` rewrites `common.scope`.
Evidence: `zend_fixup_trait_method()` changes trait method scope to the using class; that is insufficient for source-lexical class-name visibility.
Tests: `T-TRAIT-BODY-DECL-ALLOWED`, `T-TRAIT-BODY-NO-GAIN`.
Residual risk: Current Phase C and PR #20421 infrastructure do not implement this rule.
Revisit condition: If implementation cost is unacceptable, RFC must explicitly change trait body policy.

## DEC-028: `::class`

Status: accepted-for-rfc-v1
Risk IDs: RISK-014
Context: `C::class` currently produces a string and does not autoload.
Options: Check and autoload; check only if loaded; never check string production.
Decision: `C::class` remains a string operation without access check.
Normative rule: Later operations that resolve that string to a CE for semantic use perform access checks.
Consequences: Restricted class strings can be produced and compared.
Evidence: Changing `::class` to autoload would be a broad PHP behavior change.
Tests: `T-CLASS-CONST-NO-AUTOLOAD`, `T-CLASS-STRING-NEW-DENIED`.
Residual risk: Names are visible.
Revisit condition: A future symbol-hiding feature.

## DEC-029: Class existence checks

Status: accepted-for-rfc-v1
Risk IDs: RISK-004, RISK-014
Context: Probes can reveal symbol existence.
Options: Hide restricted symbols; return false when inaccessible; reveal existence but no capability.
Decision: Existence probes may reveal restricted declarations.
Normative rule: `class_exists()` and related probes do not grant later semantic access.
Consequences: The feature is not symbol hiding.
Evidence: Reflection/introspection is allowed and namespace visibility is not security.
Tests: `T-EXISTS-CLASS`, `T-EXISTS-NO-CAPABILITY`.
Residual risk: Users can discover restricted names.
Revisit condition: A separate symbol hiding/security RFC.

## DEC-030: Aliases

Status: accepted-for-rfc-v1
Risk IDs: RISK-015
Context: `class_alias()` creates another lookup name for a CE.
Options: Alias widens; alias denied; metadata stays on CE.
Decision: Alias preserves CE visibility metadata.
Normative rule: Semantic use through an alias checks the target CE's namespace visibility.
Consequences: Public-looking aliases do not bypass restrictions.
Evidence: PHP aliases refer to the same class entry.
Tests: `T-ALIAS-ALLOWED-THEN-DENIED`, `T-ALIAS-DENIED-THEN-ALLOWED`, `T-ALIAS-OPCACHE`.
Residual risk: Alias creation can reveal existence.
Revisit condition: Discovery probes are later restricted.

## DEC-031: Reflection instantiation

Status: accepted-for-rfc-v1
Risk IDs: RISK-010
Context: Reflection can inspect and sometimes bypass ordinary member visibility.
Options: Full bypass; metadata allowed but construction checked; full denial.
Decision: Reflection metadata allowed; Reflection construction checked.
Normative rule: `ReflectionClass::newInstance*()` enforces class-level namespace visibility with the call site's lexical namespace.
Consequences: Reflection is not an official construction escape hatch.
Evidence: Class-like visibility controls name/instantiation use; introspection is not semantic use.
Tests: `T-REFLECT-CLASS-ALLOWED`, `T-REFLECT-NEW-DENIED`, `T-REFLECT-NEW-WITHOUT-CTOR-DENIED`.
Residual risk: Reflection reveals metadata.
Revisit condition: Internals explicitly votes for privileged Reflection bypass.

## DEC-032: Autoload side effects

Status: accepted-for-rfc-v1
Risk IDs: RISK-009
Context: Unknown class metadata is unavailable before loading.
Options: Pre-autoload manifest; autoload then check; suppress autoload.
Decision: Autoload may run before access denial.
Normative rule: The access check uses the original operation namespace after CE resolution, not the autoloader namespace.
Consequences: Forbidden access can cause autoload side effects.
Evidence: `zend_lookup_class_ex()` autoloads before returning a CE.
Tests: `T-AUTOLOAD-SIDE-EFFECT`, `T-AUTOLOAD-CALLER-NAMESPACE`.
Residual risk: Side effects are unavoidable without separate metadata.
Revisit condition: Module/manifest RFC supplies pre-load metadata.

## DEC-033: Public API exposure

Status: accepted-for-rfc-v1
Risk IDs: RISK-011
Context: Public signatures can mention restricted types.
Options: Forbid natively; allow declaration and restrict external use; no type checks.
Decision: Allow declaration-site use when the declaring namespace can access the type.
Normative rule: External code may call such APIs but cannot semantically use the restricted type name from its own namespace.
Consequences: Public APIs can leak restricted type names.
Evidence: Native consistent accessibility is larger than v1.
Tests: `T-TYPE-PUBLIC-LEAK-ALLOWED`, `T-TYPE-EXTERNAL-USE-DENIED`.
Residual risk: API awkwardness and analyzer warnings.
Revisit condition: Consistent accessibility RFC.

## DEC-034: Consistent accessibility deferred or included

Status: deferred-from-rfc-v1
Risk IDs: RISK-011
Context: Languages often require public API types to be public.
Options: Include native consistent accessibility; defer; reject permanently.
Decision: Defer native consistent accessibility.
Normative rule: No v1 diagnostic rejects public APIs solely because they expose restricted types.
Consequences: Static analyzers should diagnose leaks as recommendations.
Evidence: Enforcement would require broader autoload, inheritance, variance, Reflection, and OPcache design.
Tests: `T-CONSISTENT-ACCESSIBILITY-NOT-NATIVE`.
Residual risk: Public APIs can expose names consumers cannot use.
Revisit condition: Separate RFC with type graph rules.

## DEC-035: Descendants included through protected(namespace)

Status: accepted-for-rfc-v1
Risk IDs: RISK-001, RISK-006
Context: Descendant namespaces are useful but introduce hierarchy semantics.
Options: Include; defer; reject.
Decision: Include descendant visibility as `protected(namespace)`.
Normative rule: Child namespaces are denied for `private(namespace)` and allowed
for `protected(namespace)` when the child relation is segment-aware.
Consequences: Bounded-context trees are supported in v1; explicit roots remain
separate.
Evidence: Exact-only visibility forces all context internals into one namespace
level and does not satisfy the motivating architecture.
Tests: `T-EXACT-CHILD-DENIED`, `T-PROT-CHILD-ALLOWED`,
`T-PROT-PREFIX-DENIED`.
Residual risk: Namespace hierarchy semantics are new for PHP class-like
visibility.
Revisit condition: Implementation cannot enforce segment-aware descendant
checks consistently.

## DEC-036: Explicit root deferred

Status: deferred-from-rfc-v1
Risk IDs: RISK-006
Context: Explicit ancestor root can broaden access beyond declaration namespace.
Options: Include root syntax; defer; reject.
Decision: Defer explicit root.
Normative rule: `private(namespace: \Root)` and `protected(namespace: \Root)`
are not valid in v1.
Consequences: No root metadata beyond declaring namespace.
Evidence: Exact-only is useful without root syntax.
Tests: `T-SYN-ROOT-NOT-V1`.
Residual risk: Moving classes across namespaces may need syntax later.
Revisit condition: RFC C.

## DEC-037: Internal reserved for modules

Status: accepted-for-rfc-v1
Risk IDs: RISK-017, RISK-005
Context: `internal` normally refers to module/package boundaries; Reflection already uses internal for engine declarations.
Options: Use `internal`; reserve; reject forever.
Decision: Reserve `internal` for future modules/packages.
Normative rule: `internal class` is not part of v1.
Consequences: Avoids claiming namespace ownership.
Evidence: C#, Kotlin, and Swift tie internal to real boundaries; PHP has none.
Tests: `T-SYN-INTERNAL-NOT-V1`.
Residual risk: Users may prefer `internal` spelling.
Revisit condition: Module/package RFC.

## DEC-038: Security boundary explicitly rejected

Status: accepted-for-rfc-v1
Risk IDs: RISK-005
Context: Any PHP file can declare any namespace.
Options: Claim security; add ownership; document non-goal.
Decision: Explicitly reject security-boundary claims.
Normative rule: Namespace visibility does not establish namespace ownership and does not isolate untrusted code.
Consequences: The feature is for cooperating codebases.
Evidence: PHP namespaces are symbol names; externals module discussions identify missing ownership.
Tests: Documentation-only `T-SEC-NAMESPACE-SPOOF-DOC`.
Residual risk: Misuse in security-sensitive docs.
Revisit condition: Modules/sandboxing feature.

## DEC-039: Error type and timing

Status: accepted-for-rfc-v1
Risk IDs: RISK-016
Context: Operations resolve CEs at compile, link, autoload, or runtime.
Options: Always runtime `Error`; compile/link fatal where existing; custom exception.
Decision: Check when CE is resolved; runtime violations throw `Error`; compile/link paths may use existing fatal errors.
Normative rule: Access is checked after target CE resolution unless rejected earlier by syntax/declaration metadata.
Consequences: Error phase follows existing PHP resolution phase.
Evidence: PHP already mixes compile/link/runtime failures for class-like declarations.
Tests: `T-ERROR-RUNTIME`, `T-ERROR-LINK`, `T-ERROR-AUTOLOAD`.
Residual risk: Some errors are not catchable.
Revisit condition: Implementation produces inconsistent timing for equivalent operations.

## DEC-040: Class-like declarations supported in v1

Status: accepted-for-rfc-v1
Risk IDs: RISK-003
Context: Scope could include only classes or all named class-like declarations.
Options: Class only; class-like; include anonymous classes.
Decision: Support named class, interface, trait, and enum declarations in v1.
Normative rule: `private(namespace)` may modify named class-like declarations; anonymous classes are excluded.
Consequences: More enforcement paths, but one coherent symbol model.
Evidence: All named class-like declarations are represented by `zend_class_entry` and can be semantically named.
Tests: `T-SCOPE-CLASS`, `T-SCOPE-INTERFACE`, `T-SCOPE-TRAIT`, `T-SCOPE-ENUM`, `T-SCOPE-ANON-REJECT`.
Residual risk: If Gate 3 cannot cover traits/interfaces/enums, scope must shrink before discussion.
Revisit condition: Enforcement coverage gate fails for class-like declarations.
