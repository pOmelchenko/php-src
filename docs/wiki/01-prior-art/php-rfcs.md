# PHP RFC Prior Art

Access date for all PHP sources: 2026-06-21.

## Namespace-Scoped Visibility for Methods and Properties

- Title: Namespace-Scoped Visibility for Methods and Properties
- Status: Under Discussion
- Version/date: Version 1.2, 2025-11-10
- Link: <https://wiki.php.net/rfc/namespace_visibility>
- Implementation: php-src PR #20421,
  <https://github.com/php/php-src/pull/20421>

Relevant points:

- Proposes `private(namespace)` for methods and properties.
- Uses lexical namespace, not runtime stack inspection.
- Requires exact namespace equality; sub-namespaces are different.
- Tracks namespace context in op arrays.
- Discusses closures, callables, first-class callables, `Closure::fromCallable`,
  `call_user_func`, and higher-order functions.
- Treats trait methods as executing under the receiver class namespace after
  composition.
- Makes runtime enforcement part of ordinary method/property access.
- Updates OPcache persistence because `zend_op_array` gains namespace metadata.
- Adds reflection metadata for namespace-private members.

What cannot be copied directly:

- It is about methods and properties, not class-like names.
- It does not define `protected(namespace)`.
- It explicitly leaves class-level visibility to future scope.
- Reflection bypass for members is not automatically appropriate for
  class-like construction or name use.
- Class-like visibility touches class linking, type resolution, `new`,
  `extends`, `implements`, `trait use`, `catch`, `instanceof`, and autoloading
  in ways member visibility does not.

## Related php-src PR #20421

Observed PR metadata:

- Title: RFC: Namespace-Scoped Visibility for Methods and Properties
- State: open
- Created: 2025-11-07
- Updated: 2026-03-14
- Base: php-src `master`
- Head branch: `bottledcode:add/private-namespace`
- Head SHA: `1618f8b0fa0a774627aa8bd1d76749cb1142000c`
- Changed files: 85
- Labels include `RFC`, `Category: Engine`, `Extension: opcache`,
  `Extension: reflection`, `Extension: spl`, `Extension: tokenizer`, and
  `ABI break`.

Reusable ideas:

- The syntax can be tokenized as a single contextual scanner token, as already
  done for `private(set)` and `protected(set)`.
- `zend_op_array` can carry lexical namespace context for functions, closures,
  and callables.
- OPcache persistence needs explicit treatment for any new string metadata.
- Tests around closures, traits, eval, callables, reflection, and inheritance
  are directly relevant.

Not reusable without redesign:

- The PR checks member visibility, not class-entry visibility.
- It uses method/property flags; class-like visibility needs
  `zend_class_entry` metadata.
- It does not cover class-table lookup, class aliases, type positions,
  inheritance linking, catch matching, or `::class`.
- It does not answer consistent accessibility for public APIs exposing
  restricted types.

## Namespace Visibility for Class, Interface and Trait

- Title: Namespace Visiblity for Class, Interface and Trait
- Status: Draft
- Version/date: Version 0.1, 2018-07-18
- Link: <https://wiki.php.net/rfc/namespace-visibility>

Relevant points:

- Directly targets class, interface, and trait visibility.
- Proposes top-level `public`, `protected`, and `private` modifiers.
- Treats instance operations on already obtained objects as allowed.
- Notes that static and name-based operations need checks.
- Mentions reflection and possible `setAccessible()`-style behavior.
- Identifies that namespaces were historically compile-time only, making
  runtime enforcement harder.

What cannot be copied directly:

- Plain `private class` and `protected class` conflict with existing PHP
  mental models and with current file-private discussions.
- The proposed `protected` namespace semantics differ from the descendant-only
  working hypothesis here.
- The RFC is old and draft; it is prior art, not a current specification.
- It does not cover enums, typed class constants, modern callable behavior,
  OPcache/JIT, or current parser/runtime details.

## Private Classes and Functions

The current official PHP RFC index was searched for a page titled "Private
classes and functions". A page with the likely slug `private_classes` returned
the PHP Wiki "topic does not exist yet" page, and the current RFC index did not
list that exact title.

Recorded conclusion:

- No current official RFC source with that exact title was found on
  2026-06-21.
- This wiki must not invent semantics for that proposal.
- Any future update should add the precise URL and status if the source is
  found or published.

## Attributes v2

- Title: Attributes v2
- Status: Implemented
- Version/date: Version 0.5, 2020-03-09
- Link: <https://wiki.php.net/rfc/attributes_v2>

Relevant points:

- Attributes are structured metadata attached to declarations.
- Attribute names are resolved similarly to class names in source.
- Internal/compiler attributes may have special engine handling.
- Reflection exposes attributes and can instantiate attribute classes.

What cannot be copied directly:

- A userland attribute such as `#[VisibleFrom('Acme\\Billing')]` cannot enforce
  parser/compiler/runtime visibility by itself.
- A built-in attribute would still require engine support and would make a core
  visibility rule look like optional metadata.
- Attribute arguments are awkward for refactoring namespaces and for expressing
  grammar-level modifier combinations.

## Friends

- Title: Friends
- Status: Under discussion
- Version/date: Version 0.4, 2026-05-04
- Link: <https://wiki.php.net/rfc/friends>
- Implementation: php-src PR #21937

Relevant points:

- Proposes explicit friend declarations for classes/enums.
- Friends can access protected members in the proposal.
- Friendship is not mutual, not transitive, and not inherited.
- The proposal mentions namespace friends as future scope.

What cannot be copied directly:

- Friend classes solve selective privilege, not namespace-tree access.
- Friend access applies to members, not class-like name use.
- The feature is intentionally opt-in per friend, while namespace visibility is
  structural.

## Class Friendship

- Title: Class Friendship
- Status: Declined
- Version/date: Version 1.0.0, 2017-09-21
- Link: <https://wiki.php.net/rfc/friend-classes>

Relevant points:

- Earlier declined attempt at friend access.
- Distinguishes class friendship from package-private or namespace visibility.

What cannot be copied directly:

- It was declined.
- It does not define namespace visibility.
- It focuses on protected member access, not class-like declaration access.

## RFC Process and Feature Proposal Policy

- PHP RFC HOWTO: <https://wiki.php.net/rfc/howto>
- Feature Proposals policy:
  <https://github.com/php/policies/blob/main/feature-proposals.rst>

Relevant process facts:

- RFCs need internals discussion and formal voting.
- Modern feature proposals have initiation, discussion, and voting phases.
- Discussion and voting windows have minimum durations.
- The primary vote requires a 2/3 majority.
- A real person must not be listed as author without consent.
- Repeated or resurrected proposals need care to avoid confusing old and new
  texts.

Implication for this work:

- The draft in [../05-rfc/draft.md](../05-rfc/draft.md) is an unassigned
  research draft.
- This repository must not present the working model as accepted.
