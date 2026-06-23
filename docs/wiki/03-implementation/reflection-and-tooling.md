# Reflection and Tooling

## Reflection Metadata

Possible Reflection additions:

```php
ReflectionClass::isNamespacePrivate(): bool
ReflectionClass::isNamespaceProtected(): bool
ReflectionClass::getNamespaceVisibilityRoot(): ?string
```

Names are placeholders. Avoid `isInternal()` because Reflection already uses
"internal" for PHP/extension-provided declarations.

## Reflection Operations

Recommended first policy:

- constructing `ReflectionClass` for a restricted class may be allowed as
  metadata inspection;
- `ReflectionClass::newInstance()`,
  `ReflectionClass::newInstanceArgs()`, and
  `ReflectionClass::newInstanceWithoutConstructor()` should enforce
  class-like visibility;
- `ReflectionMethod::invoke()` should not become a runtime membrane check if
  the object has already escaped, though existing method visibility/reflection
  rules still apply;
- `ReflectionAttribute::newInstance()` constructs an attribute class by name and
  checks namespace visibility against the lexical namespace of the attributed
  declaration.

## Stubs and Arginfo

Any Reflection API addition requires changes to:

- `ext/reflection/php_reflection.stub.php`;
- generated arginfo files under `ext/reflection`;
- reflection tests under `ext/reflection/tests`.

Generated files should not be manually edited in a final implementation. Use
the existing php-src generation workflow.

## Tokenizer

If scanner tokens such as `T_PRIVATE_NAMESPACE` and `T_PROTECTED_NAMESPACE` are
introduced, update:

- `ext/tokenizer`;
- tokenizer constants;
- tokenizer tests.

PR #20421 changes tokenizer support for member `private(namespace)` and is
useful prior art.

The current prototype exposes both namespace visibility tokens. Explicit-root
forms are not tokenized or parsed yet.

## Static Analysis and IDEs

Tools need to understand:

- new declaration modifiers;
- allowed namespace roots;
- exact vs descendant namespace checks;
- aliases preserving restricted CE metadata;
- `::class` unresolved behavior;
- public API exposure warnings;
- that Composer package ownership is not language semantics.

Recommended analyzer behavior before a final RFC:

- warn on public API exposing restricted types;
- warn on external direct use of restricted names;
- warn that `internal` is not part of the first prototype;
- model object escape through public interfaces as allowed.
