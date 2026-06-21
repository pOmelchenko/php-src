--TEST--
Namespace visibility Gate 3 type positions
--FILE--
<?php

namespace Gate3\Types\Library {
    private(namespace) class Hidden {}

    interface Marker {}

    private(namespace) class HiddenMarker implements Marker {}

    private(namespace) enum HiddenEnum {
        case A;
    }
}

namespace Gate3\Types\Consumer {
    function report(string $label, callable $callback): void {
        try {
            $callback();
            echo $label, ": ok\n";
        } catch (\Throwable $e) {
            echo $label, ': ', get_class($e), ': ', $e->getMessage(), "\n";
        }
    }

    report('parameter', function () {
        eval('namespace Gate3\Types\Consumer; function bad_parameter(\Gate3\Types\Library\Hidden $value): void {}');
        bad_parameter(new \stdClass());
    });

    report('return', function () {
        eval('namespace Gate3\Types\Consumer; function bad_return(): \Gate3\Types\Library\Hidden { return new \stdClass(); }');
        bad_return();
    });

    report('property', function () {
        eval('namespace Gate3\Types\Consumer; class BadProperty { public \Gate3\Types\Library\Hidden $value; }');
        $object = new BadProperty();
        $object->value = new \stdClass();
    });

    report('class-constant-type', fn() =>
        eval('namespace Gate3\Types\Consumer; class BadClassConstant { public const \Gate3\Types\Library\HiddenEnum VALUE = \Gate3\Types\Library\HiddenEnum::A; }')
        || BadClassConstant::VALUE
    );

    report('promoted-property', function () {
        eval('namespace Gate3\Types\Consumer; class BadPromotedProperty { public function __construct(public \Gate3\Types\Library\Hidden $value) {} }');
        new BadPromotedProperty(new \stdClass());
    });

    report('union', function () {
        eval('namespace Gate3\Types\Consumer; function bad_union(\Gate3\Types\Library\Hidden|\stdClass $value): void {}');
        bad_union(new \DateTimeImmutable());
    });

    report('intersection', function () {
        eval('namespace Gate3\Types\Consumer; function bad_intersection(\Gate3\Types\Library\HiddenMarker&\Gate3\Types\Library\Marker $value): void {}');
        bad_intersection(new \stdClass());
    });

    report('dnf', function () {
        eval('namespace Gate3\Types\Consumer; function bad_dnf((\Gate3\Types\Library\HiddenMarker&\Gate3\Types\Library\Marker)|\stdClass $value): void {}');
        bad_dnf(new \DateTimeImmutable());
    });
}

?>
--EXPECT--
parameter: Error: Cannot access private(namespace) class Gate3\Types\Library\Hidden from namespace Gate3\Types\Consumer
return: Error: Cannot access private(namespace) class Gate3\Types\Library\Hidden from namespace Gate3\Types\Consumer
property: Error: Cannot access private(namespace) class Gate3\Types\Library\Hidden from namespace Gate3\Types\Consumer
class-constant-type: Error: Cannot access private(namespace) enum Gate3\Types\Library\HiddenEnum from namespace Gate3\Types\Consumer
promoted-property: Error: Cannot access private(namespace) class Gate3\Types\Library\Hidden from namespace Gate3\Types\Consumer
union: Error: Cannot access private(namespace) class Gate3\Types\Library\Hidden from namespace Gate3\Types\Consumer
intersection: Error: Cannot access private(namespace) class Gate3\Types\Library\HiddenMarker from namespace Gate3\Types\Consumer
dnf: Error: Cannot access private(namespace) class Gate3\Types\Library\HiddenMarker from namespace Gate3\Types\Consumer
