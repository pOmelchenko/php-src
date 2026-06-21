# Compatibility and Security

## Source Compatibility

`private(namespace)` before a named class-like declaration is not valid PHP
today. Existing valid code is not reinterpreted.

Expected parser changes:

- `private(namespace) class A {}` becomes valid;
- `private(namespace) interface I {}` becomes valid;
- `private(namespace) trait T {}` becomes valid;
- `private(namespace) enum E {}` becomes valid;
- `protected(namespace)` class-like declarations become valid;
- `private class A {}` remains outside this RFC.

## Keyword and Token Impact

The syntax can be implemented as a qualified visibility token or parser
sequence. It should not reserve `namespace` beyond its current keyword role.

`token_get_all()` and `ext/tokenizer` need deterministic token output. Tooling
such as nikic/php-parser, IDEs, formatters, PHPStan, Psalm, PHPUnit mocks,
Doctrine proxies, Symfony DI, and Composer generated code will need updates
before they can parse or generate the new syntax. Compatibility of those tools
is not claimed without their own tests or issues.

## Reflection and Debug Output

Reflection modifier bit masks must not collide with existing public/protected/
private class member flags. New Reflection metadata methods are preferable to
overloading `ReflectionClass::isInternal()`.

`serialize()`, `var_dump()`, `debug_zval_dump()`, and `get_class()` may reveal
restricted class names. Class-level visibility does not hide strings or object
metadata.

## ABI/API Impact

Likely ABI-affecting changes:

- new `zend_class_entry` flags or fields;
- possibly new op-array caller namespace metadata;
- OPcache persistence format changes;
- Reflection method additions;
- class fetch API additions where caller namespace must be supplied;
- JIT guards or disabled fast paths for restricted CEs.

This is not suitable for a patch release.

## Security Non-goal

Namespace visibility does not establish ownership of a namespace and is not a
security boundary.

Any PHP file can declare:

```php
namespace Vendor\Package;
```

and receive exact namespace access. This is ACCEPTED because the mechanism is
for architectural expression in cooperating codebases, not isolation of
untrusted code. Composer package identity, filesystem ownership, module
identity, and sandboxing are outside RFC v1.
