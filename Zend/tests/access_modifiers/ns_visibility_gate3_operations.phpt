--TEST--
Namespace visibility Gate 3 VM operations and class linking
--FILE--
<?php

namespace Gate3\Operations\Library {
    private(namespace) class Hidden {
        public static string $property = 'property';
        public const VALUE = 'constant';

        public static function method(): string {
            return 'method';
        }
    }

    private(namespace) class HiddenParent {}
    private(namespace) interface HiddenInterface {}
    private(namespace) trait HiddenTrait {}
    private(namespace) class HiddenException extends \Exception {}

    function allowed_static_access(): void {
        echo Hidden::method(), "\n";
        echo Hidden::$property, "\n";
        Hidden::$property = 'changed';
        echo Hidden::$property, "\n";
        echo Hidden::VALUE, "\n";
        echo Hidden::class, "\n";
        var_dump(new Hidden() instanceof Hidden);
    }

    function throw_hidden(): void {
        throw new HiddenException('hidden');
    }
}

namespace Gate3\Operations\Consumer {
    function report(string $label, callable $callback): void {
        try {
            $value = $callback();
            echo $label, ': ', $value === null ? 'ok' : $value, "\n";
        } catch (\Throwable $e) {
            echo $label, ': ', get_class($e), ': ', $e->getMessage(), "\n";
        }
    }

    \Gate3\Operations\Library\allowed_static_access();

    report('static-method', fn() => \Gate3\Operations\Library\Hidden::method());
    report('static-property-read', fn() => \Gate3\Operations\Library\Hidden::$property);
    report('static-property-write', function () {
        \Gate3\Operations\Library\Hidden::$property = 'denied';
    });
    report('static-property-unset', function () {
        unset(\Gate3\Operations\Library\Hidden::$property);
    });
    report('class-constant', fn() => \Gate3\Operations\Library\Hidden::VALUE);
    report('class-name-constant', fn() => \Gate3\Operations\Library\Hidden::class);
    report('instanceof', fn() => new \stdClass() instanceof \Gate3\Operations\Library\Hidden);

    report('catch', function () {
        try {
            \Gate3\Operations\Library\throw_hidden();
        } catch (\Gate3\Operations\Library\HiddenException $e) {
            return 'caught';
        }
    });

    report('extends', fn() =>
        eval('namespace Gate3\Operations\Consumer; class BadParent extends \Gate3\Operations\Library\HiddenParent {}')
    );
    report('implements', fn() =>
        eval('namespace Gate3\Operations\Consumer; class BadInterface implements \Gate3\Operations\Library\HiddenInterface {}')
    );
    report('interface-extends', fn() =>
        eval('namespace Gate3\Operations\Consumer; interface BadChildInterface extends \Gate3\Operations\Library\HiddenInterface {}')
    );
    report('trait-use', fn() =>
        eval('namespace Gate3\Operations\Consumer; class BadTraitUse { use \Gate3\Operations\Library\HiddenTrait; }')
    );
}

?>
--EXPECT--
method
property
changed
constant
Gate3\Operations\Library\Hidden
bool(true)
static-method: Error: Cannot access private(namespace) class Gate3\Operations\Library\Hidden from namespace Gate3\Operations\Consumer
static-property-read: Error: Cannot access private(namespace) class Gate3\Operations\Library\Hidden from namespace Gate3\Operations\Consumer
static-property-write: Error: Cannot access private(namespace) class Gate3\Operations\Library\Hidden from namespace Gate3\Operations\Consumer
static-property-unset: Error: Cannot access private(namespace) class Gate3\Operations\Library\Hidden from namespace Gate3\Operations\Consumer
class-constant: Error: Cannot access private(namespace) class Gate3\Operations\Library\Hidden from namespace Gate3\Operations\Consumer
class-name-constant: Gate3\Operations\Library\Hidden
instanceof: Error: Cannot access private(namespace) class Gate3\Operations\Library\Hidden from namespace Gate3\Operations\Consumer
catch: Error: Cannot access private(namespace) class Gate3\Operations\Library\HiddenException from namespace Gate3\Operations\Consumer
extends: Error: Cannot access private(namespace) class Gate3\Operations\Library\HiddenParent from namespace Gate3\Operations\Consumer
implements: Error: Cannot access private(namespace) interface Gate3\Operations\Library\HiddenInterface from namespace Gate3\Operations\Consumer
interface-extends: Error: Cannot access private(namespace) interface Gate3\Operations\Library\HiddenInterface from namespace Gate3\Operations\Consumer
trait-use: Error: Cannot access private(namespace) trait Gate3\Operations\Library\HiddenTrait from namespace Gate3\Operations\Consumer
