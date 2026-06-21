--TEST--
Namespace visibility Gate 3 callables, probes, reflection, aliases, and object operations
--FILE--
<?php

namespace Gate3\Edges\Library {
    class Base {}

    private(namespace) class Hidden extends Base {
        public string $property = 'property';

        public static function staticMethod(): string {
            return 'static';
        }

        public function instanceMethod(): string {
            return 'instance';
        }
    }

    function make(): Hidden {
        return new Hidden();
    }
}

namespace Gate3\Edges\Consumer {
    function report(string $label, callable $callback): void {
        try {
            $value = $callback();
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            echo $label, ': ', $value === null ? 'ok' : $value, "\n";
        } catch (\Throwable $e) {
            echo $label, ': ', get_class($e), ': ', $e->getMessage(), "\n";
        }
    }

    $hidden = \Gate3\Edges\Library\Hidden::class;
    $object = \Gate3\Edges\Library\make();
    $serialized = serialize($object);

    report('class-exists-probe', fn() => class_exists($hidden));
    report('method-exists-probe', fn() => method_exists($hidden, 'staticMethod'));
    report('property-exists-probe', fn() => property_exists($hidden, 'property'));
    report('is-callable-probe', fn() => is_callable([$hidden, 'staticMethod']));
    report('call-user-func', fn() => call_user_func([$hidden, 'staticMethod']));
    report('closure-from-callable', fn() => \Closure::fromCallable([$hidden, 'staticMethod']));
    report('is-a', fn() => is_a($hidden, \Gate3\Edges\Library\Base::class, true));
    report('is-subclass-of', fn() => is_subclass_of($hidden, \Gate3\Edges\Library\Base::class));
    report('is-a-existing-object-target', fn() => is_a($object, $hidden));

    report('reflection-metadata', function () use ($hidden) {
        $reflection = new \ReflectionClass($hidden);
        return $reflection->getName();
    });
    report('reflection-new-instance', function () use ($hidden) {
        $reflection = new \ReflectionClass($hidden);
        $reflection->newInstanceWithoutConstructor();
    });
    report('reflection-method-existing-object', function () use ($object) {
        $method = new \ReflectionMethod($object, 'instanceMethod');
        return $method->invoke($object);
    });

    report('get-class-existing-object', fn() => get_class($object));
    report('clone-existing-object', fn() => get_class(clone $object));
    report('serialize-existing-object', fn() => str_starts_with($serialized, 'O:'));
    report('unserialize-restricted-object', fn() => unserialize($serialized));

    report('class-alias-create', function () use ($hidden) {
        return class_alias($hidden, __NAMESPACE__ . '\HiddenAlias');
    });
    report('class-alias-use', fn() => new HiddenAlias());
}

?>
--EXPECT--
class-exists-probe: true
method-exists-probe: true
property-exists-probe: true
is-callable-probe: false
call-user-func: Error: Cannot access private(namespace) class Gate3\Edges\Library\Hidden from namespace Gate3\Edges\Consumer
closure-from-callable: Error: Cannot access private(namespace) class Gate3\Edges\Library\Hidden from namespace Gate3\Edges\Consumer
is-a: Error: Cannot access private(namespace) class Gate3\Edges\Library\Hidden from namespace Gate3\Edges\Consumer
is-subclass-of: Error: Cannot access private(namespace) class Gate3\Edges\Library\Hidden from namespace Gate3\Edges\Consumer
is-a-existing-object-target: Error: Cannot access private(namespace) class Gate3\Edges\Library\Hidden from namespace Gate3\Edges\Consumer
reflection-metadata: Gate3\Edges\Library\Hidden
reflection-new-instance: Error: Cannot access private(namespace) class Gate3\Edges\Library\Hidden from namespace Gate3\Edges\Consumer
reflection-method-existing-object: instance
get-class-existing-object: Gate3\Edges\Library\Hidden
clone-existing-object: Gate3\Edges\Library\Hidden
serialize-existing-object: true
unserialize-restricted-object: Error: Cannot access private(namespace) class Gate3\Edges\Library\Hidden from namespace Gate3\Edges\Consumer
class-alias-create: true
class-alias-use: Error: Cannot access private(namespace) class Gate3\Edges\Library\Hidden from namespace Gate3\Edges\Consumer
